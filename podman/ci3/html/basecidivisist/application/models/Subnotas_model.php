<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subnotas_model extends CI_Model
{
    public $ultimo_error = null;

    public function __construct()
    {
        parent::__construct();
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    private function _normalizar_grupo($grupo)
    {
        return (!empty($grupo) && $grupo !== '') ? $grupo : '-';
    }

    private function _construir_condicion_materia($codMateria)
    {
        $mLow = strtolower(trim($codMateria));
        $mClean = preg_replace('/[^a-z0-9]/', '', $mLow);

        $candidatos = array("'" . $mLow . "'", "'" . $mClean . "'", "'curso_" . $mClean . "'");

        if (strpos($mClean, '115') === 0 && strlen($mClean) > 3) {
            $sin115 = substr($mClean, 3);
            $candidatos[] = "'" . $sin115 . "'";
            $candidatos[] = "'curso_" . $sin115 . "'";
        } else {
            $con115 = '115' . $mClean;
            $candidatos[] = "'" . $con115 . "'";
            $candidatos[] = "'curso_" . $con115 . "'";
        }

        if ($mClean === '5678' || $mClean === '1155678' || $mClean === 'matematicas') {
            $candidatos[] = "'matematicas_115'";
            $candidatos[] = "'matematicas'";
            $candidatos[] = "'5678'";
            $candidatos[] = "'1155678'";
        } elseif ($mClean === '91011' || $mClean === '11591011' || $mClean === 'pruebapractica') {
            $candidatos[] = "'prueba_practica'";
            $candidatos[] = "'cursopruebapractica'";
            $candidatos[] = "'91011'";
            $candidatos[] = "'11591011'";
        } elseif ($mClean === '1234' || $mClean === '1151234' || $mClean === 'biologia') {
            $candidatos[] = "'biologia'";
            $candidatos[] = "'1151234'";
            $candidatos[] = "'1234'";
        } elseif ($mClean === 'p' || $mClean === '115p' || $mClean === 'prueba') {
            return "LOWER(TRIM(COD_MATERIA)) IN ('p', '115p', 'curso_p', 'curso_9', 'prueba')";
        }

        $candsList = implode(',', array_unique($candidatos));
        return "LOWER(TRIM(COD_MATERIA)) IN ({$candsList})";
    }

    private function _obtener_candidatos_estudiante($codEstudiante)
    {
        $cands = array(
            "'" . strtolower(trim($codEstudiante)) . "'",
            "'" . strtoupper(trim($codEstudiante)) . "'",
            "'" . trim($codEstudiante) . "'"
        );
        $clean = strtolower(trim($codEstudiante));
        if ($clean === '1152002' || $clean === 'viviana') {
            $cands[] = "'1152002'";
            $cands[] = "'viviana'";
        } elseif ($clean === '1152001' || $clean === 'jorge' || $clean === 'jorgeandresfs') {
            $cands[] = "'1152001'";
            $cands[] = "'jorge'";
            $cands[] = "'jorgeandresfs'";
        }

        try {
            $CI =& get_instance();
            if (!isset($CI->Moodle_model)) {
                $CI->load->model('Moodle_model');
            }
            if (isset($CI->Moodle_model)) {
                $resMoodle = $CI->Moodle_model->obtener_cursos_estudiante($codEstudiante);
                if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['estudiante'])) {
                    $e = $resMoodle['datos']['estudiante'];
                    if (!empty($e['username'])) { $cands[] = "'" . strtolower(trim($e['username'])) . "'"; }
                    if (!empty($e['idnumber'])) { $cands[] = "'" . strtolower(trim($e['idnumber'])) . "'"; }
                    if (!empty($e['codigo']))   { $cands[] = "'" . strtolower(trim($e['codigo'])) . "'"; }
                }
            }
        } catch (Exception $e) {
        }

        return implode(',', array_unique($cands));
    }

    private function _obtener_siguiente_id()
    {
        $objId = null;
        $this->database2->get_sql_object("SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM SUBNOTAS", $objId);
        return ($objId && isset($objId->NEXT_ID)) ? (int)$objId->NEXT_ID : 1;
    }

    public function eliminar_subnotas_corte($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $tipoPrevioUpper = strtoupper(trim($tipoPrevio));
        $condMat = $this->_construir_condicion_materia($codMateria);
        $sql = "DELETE FROM SUBNOTAS 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND UPPER(TRIM(TIPO_PREVIO)) = '{$tipoPrevioUpper}'";
        return $this->database2->get_sql_bool($sql);
    }

    public function corte_esta_calificado($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $tipoPrevioUpper = strtoupper(trim($tipoPrevio));
        $condMat = $this->_construir_condicion_materia($codMateria);
        $sql = "SELECT COUNT(*) AS TOTAL FROM SUBNOTAS 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE    = '{$semestre}' 
                  AND UPPER(TRIM(TIPO_PREVIO)) = '{$tipoPrevioUpper}' 
                  AND (ESTADO = 'ACEPTADA' OR ESTADO = 'MODIFICADA')";
        $obj = null;
        $this->database2->get_sql_object($sql, $obj);
        if (!$obj || !isset($obj->TOTAL) || (int)$obj->TOTAL <= 0) {
            $sqlSinSem = "SELECT COUNT(*) AS TOTAL FROM SUBNOTAS 
                          WHERE {$condMat}
                            AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                            AND UPPER(TRIM(TIPO_PREVIO)) = '{$tipoPrevioUpper}' 
                            AND (ESTADO = 'ACEPTADA' OR ESTADO = 'MODIFICADA')";
            $this->database2->get_sql_object($sqlSinSem, $obj);
        }
        return ($obj && isset($obj->TOTAL) && (int)$obj->TOTAL > 0);
    }

    public function obtener_cortes_calificados($codProfesor, $codMateria, $grupo, $semestre)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $condMat = $this->_construir_condicion_materia($codMateria);
        $sql = "SELECT TIPO_PREVIO, COUNT(*) AS TOTAL FROM SUBNOTAS 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE    = '{$semestre}' 
                  AND (ESTADO = 'ACEPTADA' OR ESTADO = 'MODIFICADA') 
                GROUP BY TIPO_PREVIO";
        $arr = array();
        $this->database2->get_obj_array($sql, $arr);

        if (empty($arr)) {
            $sqlSinSem = "SELECT TIPO_PREVIO, COUNT(*) AS TOTAL FROM SUBNOTAS 
                          WHERE {$condMat}
                            AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                            AND (ESTADO = 'ACEPTADA' OR ESTADO = 'MODIFICADA') 
                          GROUP BY TIPO_PREVIO";
            $this->database2->get_obj_array($sqlSinSem, $arr);
        }

        $calificados = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $prev = isset($row->TIPO_PREVIO) ? trim($row->TIPO_PREVIO) : '';
                if (!empty($prev) && isset($row->TOTAL) && (int)$row->TOTAL > 0) {
                    $calificados[$prev] = true;
                }
            }
        }

        return $calificados;
    }

    public function guardar_subnotas_estudiante($codProfesor, $codMateria, $grupo, $semestre, $codEstudiante, $tipoPrevio, $desgloseSubnotas, $estado = 'SUGERIDA')
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $this->eliminar_subnotas_estudiante($codEstudiante, $codMateria, $grupo, $semestre, $tipoPrevio);

        if (empty($desgloseSubnotas) || !is_array($desgloseSubnotas)) {
            return false;
        }

        $exito = true;
        foreach ($desgloseSubnotas as $item) {
            $idActividad   = isset($item['id_actividad_moodle']) ? $item['id_actividad_moodle'] : 0;
            $nombre        = isset($item['nombre_actividad']) ? $item['nombre_actividad'] : '';
            $notaOriginal  = isset($item['nota_original']) ? (float)$item['nota_original'] : 0.0;
            $notaMaxima    = isset($item['nota_maxima']) ? (float)$item['nota_maxima'] : 5.0;
            $porcentaje    = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0.0;
            $subnota       = isset($item['subnota']) ? (float)$item['subnota'] : 0.0;

            $nextId = $this->_obtener_siguiente_id();

            $dataInsert = array(
                'ID'                  => $nextId,
                'COD_PROFESOR'        => $codProfesor,
                'COD_MATERIA'         => $codMateria,
                'GRUPO'               => $grupo,
                'SEMESTRE'            => $semestre,
                'COD_ESTUDIANTE'      => $codEstudiante,
                'TIPO_PREVIO'         => $tipoPrevio,
                'ID_ACTIVIDAD_MOODLE' => $idActividad,
                'NOMBRE_ACTIVIDAD'    => $nombre,
                'NOTA_ORIGINAL'       => $notaOriginal,
                'NOTA_MAXIMA'         => $notaMaxima,
                'PORCENTAJE'          => $porcentaje,
                'SUBNOTA'             => $subnota,
                'ESTADO'              => $estado,
            );

            $res = $this->database2->insert('SUBNOTAS', $dataInsert);
            if (!$res) {
                $conn = $this->database2->get_conn();
                $err = oci_error($conn);
                $this->ultimo_error = isset($err['message']) ? $err['message'] : 'Error en insercion SUBNOTAS';
                $exito = false;
            }
        }

        return $exito;
    }

    public function obtener_subnotas_estudiante($codEstudiante, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $inEst = $this->_obtener_candidatos_estudiante($codEstudiante);
        $condMat = $this->_construir_condicion_materia($codMateria);

        $sql = "SELECT ID, COD_ESTUDIANTE, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                       NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO 
                FROM SUBNOTAS 
                WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ({$inEst}) 
                  AND {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND (TIPO_PREVIO = '{$tipoPrevio}' 
                       OR ('{$tipoPrevio}' IN ('FINAL', 'EXA', '4') AND TIPO_PREVIO IN ('FINAL', 'EXA', '4'))) 
                ORDER BY ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        return $arr;
    }

    public function eliminar_subnotas_estudiante($codEstudiante, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $inEst = $this->_obtener_candidatos_estudiante($codEstudiante);

        $sql = "DELETE FROM SUBNOTAS 
                WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ({$inEst}) 
                  AND COD_MATERIA    = '{$codMateria}' 
                  AND GRUPO          = '{$grupo}' 
                  AND SEMESTRE       = '{$semestre}' 
                  AND TIPO_PREVIO    = '{$tipoPrevio}'";

        return $this->database2->get_sql_bool($sql);
    }

    public function obtener_resumen_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $condMat = $this->_construir_condicion_materia($codMateria);
        $sql = "SELECT COD_ESTUDIANTE, 
                       ROUND(SUM(SUBNOTA), 2) AS NOTA_SUGERIDA,
                       COUNT(ID) AS TOTAL_ACTIVIDADES,
                       MAX(ESTADO) AS ESTADO_GENERAL
                FROM SUBNOTAS 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE    = '{$semestre}' 
                  AND TIPO_PREVIO = '{$tipoPrevio}' 
                GROUP BY COD_ESTUDIANTE 
                ORDER BY COD_ESTUDIANTE ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);

        if (empty($arr)) {
            $sqlSinSem = "SELECT COD_ESTUDIANTE, 
                                 ROUND(SUM(SUBNOTA), 2) AS NOTA_SUGERIDA,
                                 COUNT(ID) AS TOTAL_ACTIVIDADES,
                                 MAX(ESTADO) AS ESTADO_GENERAL
                          FROM SUBNOTAS 
                          WHERE {$condMat}
                            AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                            AND TIPO_PREVIO = '{$tipoPrevio}' 
                          GROUP BY COD_ESTUDIANTE 
                          ORDER BY COD_ESTUDIANTE ASC";
            $this->database2->get_obj_array($sqlSinSem, $arr);
        }

        return $arr;
    }

    public function actualizar_estado_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $nuevoEstado)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $sql = "UPDATE SUBNOTAS SET ESTADO = '{$nuevoEstado}' 
                WHERE COD_MATERIA = '{$codMateria}' 
                  AND GRUPO       = '{$grupo}' 
                  AND SEMESTRE    = '{$semestre}' 
                  AND TIPO_PREVIO = '{$tipoPrevio}'";

        return $this->database2->get_sql_bool($sql);
    }

    public function obtener_todas_subnotas_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $condMat = $this->_construir_condicion_materia($codMateria);

        $sql = "SELECT ID, COD_ESTUDIANTE, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                       NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO 
                FROM SUBNOTAS 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE    = '{$semestre}' 
                  AND TIPO_PREVIO = '{$tipoPrevio}' 
                ORDER BY COD_ESTUDIANTE ASC, ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        if (empty($arr)) {
            $sqlSinSem = "SELECT ID, COD_ESTUDIANTE, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                                 NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO 
                          FROM SUBNOTAS 
                          WHERE {$condMat}
                            AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                            AND TIPO_PREVIO = '{$tipoPrevio}' 
                          ORDER BY COD_ESTUDIANTE ASC, ID ASC";
            $this->database2->get_obj_array($sqlSinSem, $arr);
        }

        $agrupadas = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $cod = isset($row->COD_ESTUDIANTE) ? trim($row->COD_ESTUDIANTE) : '';
                if (!empty($cod)) {
                    if (!isset($agrupadas[$cod])) {
                        $agrupadas[$cod] = array(
                            'items'      => array(),
                            'estado'     => isset($row->ESTADO) ? trim($row->ESTADO) : 'SUGERIDA',
                            'total_nota' => 0.0,
                        );
                    }
                    $agrupadas[$cod]['items'][] = $row;
                    $agrupadas[$cod]['total_nota'] += isset($row->SUBNOTA) ? (float)$row->SUBNOTA : 0.0;
                }
            }
        }

        return $agrupadas;
    }

    public function guardar_calificaciones_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $listaEstudiantes)
    {
        if (empty($listaEstudiantes) || !is_array($listaEstudiantes)) {
            return false;
        }

        $exitoGeneral = true;

        foreach ($listaEstudiantes as $est) {
            $codEstudiante  = isset($est['cod_estudiante']) ? $est['cod_estudiante'] : '';
            $notaDefinitiva = isset($est['nota_definitiva']) ? (float)$est['nota_definitiva'] : 0.0;
            $notaSugerida   = isset($est['nota_sugerida']) ? (float)$est['nota_sugerida'] : 0.0;
            $estado         = isset($est['estado']) ? $est['estado'] : 'SUGERIDA';
            $desglose       = isset($est['desglose']) ? $est['desglose'] : array();

            if (empty($codEstudiante)) {
                continue;
            }

            $this->eliminar_subnotas_estudiante($codEstudiante, $codMateria, $grupo, $semestre, $tipoPrevio);

            if (empty($desglose) || !is_array($desglose)) {
                continue;
            }

            foreach ($desglose as $item) {
                $idActividad  = isset($item['id_actividad_moodle']) ? $item['id_actividad_moodle'] : 0;
                $nombre       = isset($item['nombre_actividad']) ? $item['nombre_actividad'] : '';
                $notaOriginal = isset($item['nota_original']) ? (float)$item['nota_original'] : 0.0;
                $notaMaxima   = isset($item['nota_maxima']) ? (float)$item['nota_maxima'] : 5.0;
                $porcentaje   = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0.0;

                $notaNorm = ($notaMaxima > 0 && abs($notaMaxima - 5.0) > 0.01) ? round(($notaOriginal / $notaMaxima) * 5.0, 2) : $notaOriginal;
                $subnotaFinal = round($notaNorm * ($porcentaje / 100.0), 2);

                $nextId = $this->_obtener_siguiente_id();

                $dataInsert = array(
                    'ID'                  => $nextId,
                    'COD_PROFESOR'        => $codProfesor,
                    'COD_MATERIA'         => $codMateria,
                    'GRUPO'               => $grupo,
                    'SEMESTRE'            => $semestre,
                    'COD_ESTUDIANTE'      => $codEstudiante,
                    'TIPO_PREVIO'         => $tipoPrevio,
                    'ID_ACTIVIDAD_MOODLE' => $idActividad,
                    'NOMBRE_ACTIVIDAD'    => $nombre,
                    'NOTA_ORIGINAL'       => $notaOriginal,
                    'NOTA_MAXIMA'         => $notaMaxima,
                    'PORCENTAJE'          => $porcentaje,
                    'SUBNOTA'             => $subnotaFinal,
                    'ESTADO'              => $estado,
                );

                $res = $this->database2->insert('SUBNOTAS', $dataInsert);
                if (!$res) {
                    $conn = $this->database2->get_conn();
                    $err = oci_error($conn);
                    $this->ultimo_error = isset($err['message']) ? $err['message'] : 'Error en insercion SUBNOTAS';
                    $exitoGeneral = false;
                }
            }

            $colNota = 'NOTA1';
            $colEst = 'EST_NOTA1';
            $tp = strtoupper(trim($tipoPrevio));
            if ($tp === '2' || $tp === '2P' || $tp === 'CORTE2') {
                $colNota = 'NOTA2';
                $colEst = 'EST_NOTA2';
            } elseif ($tp === '3' || $tp === '3P' || $tp === 'CORTE3') {
                $colNota = 'NOTA3';
                $colEst = 'EST_NOTA3';
            } elseif ($tp === 'FINAL' || $tp === 'EX' || $tp === 'EXA') {
                $colNota = 'EXA';
                $colEst = 'EST_EXA';
            }

            $carreraEst = strlen($codEstudiante) >= 3 ? substr($codEstudiante, 0, 3) : '115';
            $alumnoEst  = strlen($codEstudiante) >= 7 ? substr($codEstudiante, 3, 4) : $codEstudiante;
            $notaCorteFinal = round(min(5.0, max(0.0, $notaSugerida > 0 ? $notaSugerida : $notaDefinitiva)), 2);

            $sqlUpdatePensum = "UPDATE MATRICULA.materiasMatriculadasPensum 
                                SET {$colNota} = {$notaCorteFinal}, {$colEst} = 'V', RUBRICA = 1 
                                WHERE COD_CARRERA = '{$carreraEst}' 
                                  AND COD_ALUMNO = '{$alumnoEst}' 
                                  AND (CODMAT = '{$codMateria}' OR CODMATMAT = '{$codMateria}' OR '115' || CODMAT = '{$codMateria}')";
            $this->database2->get_sql_bool($sqlUpdatePensum);
        }

        $conn = $this->database2->get_conn();
        if ($conn) {
            @oci_commit($conn);
            @oci_close($conn);
        }

        return $exitoGeneral;
    }

    public function obtener_calificaciones_todos_cortes($codProfesor, $codMateria, $grupo, $semestre)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $condMat = $this->_construir_condicion_materia($codMateria);

        $sql = "SELECT COD_ESTUDIANTE, TIPO_PREVIO, 
                       ROUND(SUM(SUBNOTA), 2) AS NOTA_CORTE,
                       MAX(ESTADO) AS ESTADO_CORTE,
                       COUNT(ID) AS TOTAL_ACTIVIDADES
                FROM SUBNOTAS 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE    = '{$semestre}' 
                GROUP BY COD_ESTUDIANTE, TIPO_PREVIO 
                ORDER BY COD_ESTUDIANTE ASC, TIPO_PREVIO ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        if (empty($arr)) {
            $sqlSinSem = "SELECT COD_ESTUDIANTE, TIPO_PREVIO, 
                                 ROUND(SUM(SUBNOTA), 2) AS NOTA_CORTE,
                                 MAX(ESTADO) AS ESTADO_CORTE,
                                 COUNT(ID) AS TOTAL_ACTIVIDADES
                          FROM SUBNOTAS 
                          WHERE {$condMat}
                            AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                          GROUP BY COD_ESTUDIANTE, TIPO_PREVIO 
                          ORDER BY COD_ESTUDIANTE ASC, TIPO_PREVIO ASC";
            $this->database2->get_obj_array($sqlSinSem, $arr);
        }

        $agrupadas = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $cod = isset($row->COD_ESTUDIANTE) ? trim($row->COD_ESTUDIANTE) : '';
                $previo = isset($row->TIPO_PREVIO) ? trim($row->TIPO_PREVIO) : '';
                if (!empty($cod) && !empty($previo)) {
                    if (!isset($agrupadas[$cod])) {
                        $agrupadas[$cod] = array();
                    }
                    $agrupadas[$cod][$previo] = array(
                        'nota'   => isset($row->NOTA_CORTE) ? (float)$row->NOTA_CORTE : 0.0,
                        'estado' => isset($row->ESTADO_CORTE) ? trim($row->ESTADO_CORTE) : '',
                        'total'  => isset($row->TOTAL_ACTIVIDADES) ? (int)$row->TOTAL_ACTIVIDADES : 0,
                    );
                }
            }
        }

        return $agrupadas;
    }

    public function obtener_todas_subnotas_curso($codProfesor, $codMateria, $grupo, $semestre)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $condMat = $this->_construir_condicion_materia($codMateria);

        $sql = "SELECT ID, COD_ESTUDIANTE, TIPO_PREVIO, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                       NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO 
                FROM SUBNOTAS 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE    = '{$semestre}' 
                ORDER BY COD_ESTUDIANTE ASC, TIPO_PREVIO ASC, ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);

        if (empty($arr)) {
            $sqlSinSem = "SELECT ID, COD_ESTUDIANTE, TIPO_PREVIO, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                                 NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO 
                          FROM SUBNOTAS 
                          WHERE {$condMat}
                            AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                          ORDER BY COD_ESTUDIANTE ASC, TIPO_PREVIO ASC, ID ASC";
            $this->database2->get_obj_array($sqlSinSem, $arr);
        }

        $agrupadas = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $cod = isset($row->COD_ESTUDIANTE) ? trim($row->COD_ESTUDIANTE) : '';
                $previo = isset($row->TIPO_PREVIO) ? trim($row->TIPO_PREVIO) : '';
                if (!empty($cod) && !empty($previo)) {
                    if (!isset($agrupadas[$cod])) {
                        $agrupadas[$cod] = array();
                    }
                    if (!isset($agrupadas[$cod][$previo])) {
                        $agrupadas[$cod][$previo] = array();
                    }
                    $agrupadas[$cod][$previo][] = $row;
                }
            }
        }

        return $agrupadas;
    }

    public function obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $CI =& get_instance();
        if (!isset($CI->Rubrica_model)) {
            $CI->load->model('Rubrica_model');
        }
        return $CI->Rubrica_model->obtener($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
    }

    public function obtener_todas_rubricas_curso($codProfesor, $codMateria, $grupo, $semestre)
    {
        $CI =& get_instance();
        if (!isset($CI->Rubrica_model)) {
            $CI->load->model('Rubrica_model');
        }
        return $CI->Rubrica_model->obtener_todas_del_curso($codProfesor, $codMateria, $grupo, $semestre);
    }
}

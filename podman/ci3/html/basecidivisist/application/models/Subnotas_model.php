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

    public function guardar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $items)
    {
        $this->eliminar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        if (empty($items) || !is_array($items)) {
            return false;
        }

        $exito = true;
        foreach ($items as $item) {
            $idActividad = isset($item['id_actividad_moodle']) ? $item['id_actividad_moodle'] : 0;
            $nombre      = isset($item['nombre_actividad']) ? $item['nombre_actividad'] : '';
            $tipo        = isset($item['tipo_actividad']) ? $item['tipo_actividad'] : '';
            $porcentaje  = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0;

            $objId = null;
            $this->database2->get_sql_object("SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM CONFIG_RUBRICA", $objId);
            $nextId = ($objId && isset($objId->NEXT_ID)) ? (int)$objId->NEXT_ID : 1;

            $dataInsert = array(
                'ID'                  => $nextId,
                'COD_PROFESOR'        => $codProfesor,
                'COD_MATERIA'         => $codMateria,
                'GRUPO'               => $grupo,
                'SEMESTRE'            => $semestre,
                'TIPO_PREVIO'         => $tipoPrevio,
                'ID_ACTIVIDAD_MOODLE' => $idActividad,
                'NOMBRE_ACTIVIDAD'    => $nombre,
                'TIPO_ACTIVIDAD'      => $tipo,
                'PORCENTAJE'          => $porcentaje,
            );

            $res = $this->database2->insert('CONFIG_RUBRICA', $dataInsert);
            if (!$res) {
                $conn = $this->database2->get_conn();
                $err = oci_error($conn);
                $this->ultimo_error = isset($err['message']) ? $err['message'] : 'Error en insercion CONFIG_RUBRICA';
                $exito = false;
            }
        }

        return $exito;
    }

    public function obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $sql = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                       ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                FROM CONFIG_RUBRICA 
                WHERE COD_PROFESOR = '{$codProfesor}' 
                  AND COD_MATERIA  = '{$codMateria}' 
                  AND GRUPO        = '{$grupo}' 
                  AND SEMESTRE     = '{$semestre}' 
                  AND TIPO_PREVIO  = '{$tipoPrevio}' 
                ORDER BY ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        return $arr;
    }

    public function obtener_todas_rubricas_curso($codProfesor, $codMateria, $grupo, $semestre)
    {
        $sql = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                       ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                FROM CONFIG_RUBRICA 
                WHERE COD_PROFESOR = '{$codProfesor}' 
                  AND COD_MATERIA  = '{$codMateria}' 
                  AND GRUPO        = '{$grupo}' 
                  AND SEMESTRE     = '{$semestre}' 
                ORDER BY TIPO_PREVIO ASC, ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);

        $agrupadas = array(
            '1'     => array(),
            '2'     => array(),
            '3'     => array(),
            'FINAL' => array(),
        );

        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $previo = isset($row->TIPO_PREVIO) ? $row->TIPO_PREVIO : '1';
                if (!isset($agrupadas[$previo])) {
                    $agrupadas[$previo] = array();
                }
                $agrupadas[$previo][] = $row;
            }
        }

        return $agrupadas;
    }

    public function eliminar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $where = array(
            'COD_PROFESOR' => $codProfesor,
            'COD_MATERIA'  => $codMateria,
            'GRUPO'        => $grupo,
            'SEMESTRE'     => $semestre,
            'TIPO_PREVIO'  => $tipoPrevio,
        );

        return $this->database2->delete('CONFIG_RUBRICA', $where);
    }

    public function guardar_subnotas_estudiante($codProfesor, $codMateria, $grupo, $semestre, $codEstudiante, $tipoPrevio, $desgloseSubnotas, $estado = 'SUGERIDA')
    {
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

            $objId = null;
            $this->database2->get_sql_object("SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM SUBNOTAS", $objId);
            $nextId = ($objId && isset($objId->NEXT_ID)) ? (int)$objId->NEXT_ID : 1;

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
        $sql = "SELECT ID, COD_ESTUDIANTE, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                       NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO 
                FROM SUBNOTAS 
                WHERE COD_ESTUDIANTE = '{$codEstudiante}' 
                  AND COD_MATERIA    = '{$codMateria}' 
                  AND GRUPO          = '{$grupo}' 
                  AND SEMESTRE       = '{$semestre}' 
                  AND TIPO_PREVIO    = '{$tipoPrevio}' 
                ORDER BY ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        return $arr;
    }

    public function eliminar_subnotas_estudiante($codEstudiante, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $where = array(
            'COD_ESTUDIANTE' => $codEstudiante,
            'COD_MATERIA'    => $codMateria,
            'GRUPO'          => $grupo,
            'SEMESTRE'       => $semestre,
            'TIPO_PREVIO'    => $tipoPrevio,
        );

        return $this->database2->delete('SUBNOTAS', $where);
    }

    public function obtener_resumen_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $sql = "SELECT COD_ESTUDIANTE, 
                       ROUND(SUM(SUBNOTA), 2) AS NOTA_SUGERIDA,
                       COUNT(ID) AS TOTAL_ACTIVIDADES,
                       MAX(ESTADO) AS ESTADO_GENERAL
                FROM SUBNOTAS 
                WHERE COD_PROFESOR = '{$codProfesor}' 
                  AND COD_MATERIA  = '{$codMateria}' 
                  AND GRUPO        = '{$grupo}' 
                  AND SEMESTRE     = '{$semestre}' 
                  AND TIPO_PREVIO  = '{$tipoPrevio}' 
                GROUP BY COD_ESTUDIANTE 
                ORDER BY COD_ESTUDIANTE ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        return $arr;
    }

    public function actualizar_estado_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $nuevoEstado)
    {
        $set = array('ESTADO' => $nuevoEstado);
        $where = array(
            'COD_PROFESOR' => $codProfesor,
            'COD_MATERIA'  => $codMateria,
            'GRUPO'        => $grupo,
            'SEMESTRE'     => $semestre,
            'TIPO_PREVIO'  => $tipoPrevio,
        );

        return $this->database2->update('SUBNOTAS', $set, $where);
    }

    public function obtener_todas_subnotas_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $sql = "SELECT ID, COD_ESTUDIANTE, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                       NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO 
                FROM SUBNOTAS 
                WHERE COD_PROFESOR = '{$codProfesor}' 
                  AND COD_MATERIA  = '{$codMateria}' 
                  AND GRUPO        = '{$grupo}' 
                  AND SEMESTRE     = '{$semestre}' 
                  AND TIPO_PREVIO  = '{$tipoPrevio}' 
                ORDER BY COD_ESTUDIANTE ASC, ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);

        $agrupadas = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $cod = isset($row->COD_ESTUDIANTE) ? $row->COD_ESTUDIANTE : '';
                if (!isset($agrupadas[$cod])) {
                    $agrupadas[$cod] = array(
                        'items'      => array(),
                        'estado'     => isset($row->ESTADO) ? $row->ESTADO : 'SUGERIDA',
                        'total_nota' => 0.0,
                    );
                }
                $agrupadas[$cod]['items'][] = $row;
                $agrupadas[$cod]['total_nota'] += isset($row->SUBNOTA) ? (float)$row->SUBNOTA : 0.0;
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

            $factorAjuste = 1.0;
            if ($estado === 'MODIFICADA') {
                if ($notaSugerida > 0) {
                    $factorAjuste = $notaDefinitiva / $notaSugerida;
                }
            }

            foreach ($desglose as $item) {
                $idActividad  = isset($item['id_actividad_moodle']) ? $item['id_actividad_moodle'] : 0;
                $nombre       = isset($item['nombre_actividad']) ? $item['nombre_actividad'] : '';
                $notaOriginal = isset($item['nota_original']) ? (float)$item['nota_original'] : 0.0;
                $notaMaxima   = isset($item['nota_maxima']) ? (float)$item['nota_maxima'] : 5.0;
                $porcentaje   = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0.0;
                $subnotaBase  = isset($item['subnota']) ? (float)$item['subnota'] : 0.0;

                if ($estado === 'MODIFICADA') {
                    if ($notaSugerida > 0) {
                        $subnotaFinal = round($subnotaBase * $factorAjuste, 2);
                    } else {
                        $subnotaFinal = round($notaDefinitiva * ($porcentaje / 100), 2);
                    }
                } else {
                    $subnotaFinal = $subnotaBase;
                }

                $objId = null;
                $this->database2->get_sql_object("SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM SUBNOTAS", $objId);
                $nextId = ($objId && isset($objId->NEXT_ID)) ? (int)$objId->NEXT_ID : 1;

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
        }

        return $exitoGeneral;
    }

    public function obtener_calificaciones_todos_cortes($codProfesor, $codMateria, $grupo, $semestre)
    {
        $sql = "SELECT COD_ESTUDIANTE, TIPO_PREVIO, 
                       ROUND(SUM(SUBNOTA), 2) AS NOTA_CORTE,
                       MAX(ESTADO) AS ESTADO_CORTE,
                       COUNT(ID) AS TOTAL_ACTIVIDADES
                FROM SUBNOTAS 
                WHERE COD_PROFESOR = '{$codProfesor}' 
                  AND COD_MATERIA  = '{$codMateria}' 
                  AND GRUPO        = '{$grupo}' 
                  AND SEMESTRE     = '{$semestre}' 
                GROUP BY COD_ESTUDIANTE, TIPO_PREVIO 
                ORDER BY COD_ESTUDIANTE ASC, TIPO_PREVIO ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);

        $agrupadas = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $cod = isset($row->COD_ESTUDIANTE) ? $row->COD_ESTUDIANTE : '';
                $previo = isset($row->TIPO_PREVIO) ? $row->TIPO_PREVIO : '';
                if (!isset($agrupadas[$cod])) {
                    $agrupadas[$cod] = array();
                }
                $agrupadas[$cod][$previo] = array(
                    'nota'   => isset($row->NOTA_CORTE) ? (float)$row->NOTA_CORTE : 0.0,
                    'estado' => isset($row->ESTADO_CORTE) ? $row->ESTADO_CORTE : '',
                    'total'  => isset($row->TOTAL_ACTIVIDADES) ? (int)$row->TOTAL_ACTIVIDADES : 0,
                );
            }
        }

        return $agrupadas;
    }

    public function obtener_todas_subnotas_curso($codProfesor, $codMateria, $grupo, $semestre)
    {
        $sql = "SELECT ID, COD_ESTUDIANTE, TIPO_PREVIO, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, 
                       NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO 
                FROM SUBNOTAS 
                WHERE COD_PROFESOR = '{$codProfesor}' 
                  AND COD_MATERIA  = '{$codMateria}' 
                  AND GRUPO        = '{$grupo}' 
                  AND SEMESTRE     = '{$semestre}' 
                ORDER BY COD_ESTUDIANTE ASC, TIPO_PREVIO ASC, ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);

        $agrupadas = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $row) {
                $cod = isset($row->COD_ESTUDIANTE) ? $row->COD_ESTUDIANTE : '';
                $previo = isset($row->TIPO_PREVIO) ? $row->TIPO_PREVIO : '';
                if (!isset($agrupadas[$cod])) {
                    $agrupadas[$cod] = array();
                }
                if (!isset($agrupadas[$cod][$previo])) {
                    $agrupadas[$cod][$previo] = array();
                }
                $agrupadas[$cod][$previo][] = $row;
            }
        }

        return $agrupadas;
    }
}

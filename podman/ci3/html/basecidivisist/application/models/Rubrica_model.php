<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rubrica_model extends CI_Model
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
        $CI =& get_instance();
        if (!isset($CI->Materia_model)) {
            $CI->load->model('Materia_model');
        }
        $can = $CI->Materia_model->obtener_mapeo_canonical($codMateria);
        if ($can !== null) {
            $c = "'" . $can['cod_materia'] . "'";
            return "LOWER(TRIM(COD_MATERIA)) IN ({$c})";
        }

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

        $candsList = implode(',', array_unique($candidatos));
        return "LOWER(TRIM(COD_MATERIA)) IN ({$candsList})";
    }

    private function _obtener_siguiente_id()
    {
        $objId = null;
        $this->database2->get_sql_object("SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM CONFIG_RUBRICA", $objId);
        return ($objId && isset($objId->NEXT_ID)) ? (int)$objId->NEXT_ID : 1;
    }

    public function guardar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $items)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $tipoPrevioUpper = strtoupper(trim($tipoPrevio));
        $this->eliminar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevioUpper);

        if (empty($items) || !is_array($items)) {
            return false;
        }

        $exito = true;
        $nextId = $this->_obtener_siguiente_id();

        foreach ($items as $item) {
            $idActividad = isset($item['id_actividad_moodle']) ? (int)$item['id_actividad_moodle'] : 0;
            $nombre      = isset($item['nombre_actividad']) ? str_replace("'", "''", trim($item['nombre_actividad'])) : '';
            $tipo        = isset($item['tipo_actividad']) ? trim($item['tipo_actividad']) : '';
            $porcentaje  = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0;

            $dataInsert = array(
                'ID'                  => $nextId++,
                'COD_PROFESOR'        => $codProfesor,
                'COD_MATERIA'         => $codMateria,
                'GRUPO'               => $grupo,
                'SEMESTRE'            => $semestre,
                'TIPO_PREVIO'         => $tipoPrevioUpper,
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

    public function obtener($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $tipoPrevioUpper = strtoupper(trim($tipoPrevio));
        $condMat = $this->_construir_condicion_materia($codMateria);

        $sql = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                       ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                FROM CONFIG_RUBRICA 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE = '{$semestre}' 
                  AND UPPER(TRIM(TIPO_PREVIO)) = '{$tipoPrevioUpper}' 
                ORDER BY ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        if (empty($arr)) {
            $sqlSinSem = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                                 ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                          FROM CONFIG_RUBRICA 
                          WHERE {$condMat}
                            AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                            AND UPPER(TRIM(TIPO_PREVIO)) = '{$tipoPrevioUpper}' 
                          ORDER BY ID ASC";
            $this->database2->get_obj_array($sqlSinSem, $arr);
        }
        return $arr;
    }

    public function obtener_todas_del_curso($codProfesor, $codMateria, $grupo, $semestre)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $condMat = $this->_construir_condicion_materia($codMateria);

        $sql = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                       ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                FROM CONFIG_RUBRICA 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND SEMESTRE = '{$semestre}' 
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
                $previo = isset($row->TIPO_PREVIO) ? strtoupper(trim($row->TIPO_PREVIO)) : '1';
                if (!isset($agrupadas[$previo])) {
                    $agrupadas[$previo] = array();
                }
                $agrupadas[$previo][] = $row;
            }
        }

        foreach (array('1', '2', '3', 'FINAL') as $cClave) {
            if (empty($agrupadas[$cClave])) {
                $sqlCorte = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                                    ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                             FROM CONFIG_RUBRICA 
                             WHERE {$condMat}
                               AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                               AND UPPER(TRIM(TIPO_PREVIO)) = '{$cClave}' 
                             ORDER BY ID ASC";
                $arrCorte = array();
                $this->database2->get_obj_array($sqlCorte, $arrCorte);
                if (!empty($arrCorte)) {
                    $agrupadas[$cClave] = $arrCorte;
                }
            }
        }

        return $agrupadas;
    }

    public function eliminar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $tipoPrevioUpper = strtoupper(trim($tipoPrevio));
        $condMat = $this->_construir_condicion_materia($codMateria);
        $sql = "DELETE FROM CONFIG_RUBRICA 
                WHERE {$condMat}
                  AND (GRUPO = '{$grupo}' OR GRUPO = '-' OR '{$grupo}' = '-' OR '{$grupo}' = '') 
                  AND UPPER(TRIM(TIPO_PREVIO)) = '{$tipoPrevioUpper}'";
        return $this->database2->get_sql_bool($sql);
    }

    public function obtener_cursos_con_rubricas($codProfesor)
    {
        $codProfesor = trim($codProfesor);
        $sql = "SELECT COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, COUNT(*) AS TOTAL_ITEMS
                FROM CONFIG_RUBRICA 
                WHERE COD_PROFESOR = '{$codProfesor}' 
                GROUP BY COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO 
                ORDER BY SEMESTRE DESC, COD_MATERIA ASC, GRUPO ASC, TIPO_PREVIO ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        if (empty($arr)) {
            $sqlSinProf = "SELECT COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, COUNT(*) AS TOTAL_ITEMS
                           FROM CONFIG_RUBRICA 
                           GROUP BY COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO 
                           ORDER BY SEMESTRE DESC, COD_MATERIA ASC, GRUPO ASC, TIPO_PREVIO ASC";
            $this->database2->get_obj_array($sqlSinProf, $arr);
        }
        return $arr;
    }
}

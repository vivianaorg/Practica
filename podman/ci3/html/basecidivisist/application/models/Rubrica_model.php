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

    private function _obtener_siguiente_id()
    {
        $objId = null;
        $this->database2->get_sql_object("SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM CONFIG_RUBRICA", $objId);
        return ($objId && isset($objId->NEXT_ID)) ? (int)$objId->NEXT_ID : 1;
    }

    public function guardar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $items)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $this->eliminar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        if (empty($items) || !is_array($items)) {
            return false;
        }

        $exito = true;
        foreach ($items as $item) {
            $idActividad = isset($item['id_actividad_moodle']) ? $item['id_actividad_moodle'] : 0;
            $nombre      = isset($item['nombre_actividad']) ? $item['nombre_actividad'] : '';
            $tipo        = isset($item['tipo_actividad']) ? $item['tipo_actividad'] : '';
            $porcentaje  = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0;

            $nextId = $this->_obtener_siguiente_id();

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

    public function obtener($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);

        $sql = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                       ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                FROM CONFIG_RUBRICA 
                WHERE COD_MATERIA = '{$codMateria}' 
                  AND GRUPO       = '{$grupo}' 
                  AND SEMESTRE    = '{$semestre}' 
                  AND TIPO_PREVIO = '{$tipoPrevio}' 
                ORDER BY ID ASC";

        $arr = array();
        $this->database2->get_obj_array($sql, $arr);
        return $arr;
    }

    public function obtener_todas_del_curso($codProfesor, $codMateria, $grupo, $semestre)
    {
        $grupo = $this->_normalizar_grupo($grupo);

        $sql = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, 
                       ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE 
                FROM CONFIG_RUBRICA 
                WHERE COD_MATERIA = '{$codMateria}' 
                  AND GRUPO       = '{$grupo}' 
                  AND SEMESTRE    = '{$semestre}' 
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

    public function eliminar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)
    {
        $grupo = $this->_normalizar_grupo($grupo);
        $sql = "DELETE FROM CONFIG_RUBRICA 
                WHERE COD_MATERIA = '{$codMateria}' 
                  AND GRUPO       = '{$grupo}' 
                  AND SEMESTRE    = '{$semestre}' 
                  AND TIPO_PREVIO = '{$tipoPrevio}'";
        return $this->database2->get_sql_bool($sql);
    }

    /**
     * Obtiene los cursos (materia, grupo, semestre) y cortes que tienen rúbricas
     * configuradas para un profesor específico.
     *
     * @param string $codProfesor
     * @return array
     */
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
        return $arr;
    }
}

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
}

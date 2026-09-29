<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modelo para la gestión de Rúbricas de Moodle y Subnotas en Divisist.
 * 
 * Permite almacenar la configuración de actividades/porcentajes por corte,
 * calcular las notas sugeridas por estudiante y consultar el desglose
 * detallado para la vista del estudiante ("Ver más").
 * 
 * Compatible con PHP 5.6 y motor Oracle (vía Database2 library).
 */
class Subnotas_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        // Carga la librería de conexión OCI8 si no está cargada
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    /**
     * Guarda la configuración de una rúbrica para un previo/corte específico.
     * Si ya existía una rúbrica para esa materia, grupo y previo, la reemplaza.
     *
     * @param string $codProfesor
     * @param string $codMateria
     * @param string $grupo
     * @param string $semestre
     * @param string $tipoPrevio  ('1', '2', '3', etc.)
     * @param array  $items       Array de arrays con: id_actividad_moodle, nombre_actividad, tipo_actividad, porcentaje
     * @return bool
     */
    public function guardar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $items)
    {
        // 1. Eliminar configuración existente previa para evitar duplicados
        $this->eliminar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        if (empty($items) || !is_array($items)) {
            return false;
        }

        $exito = true;
        foreach ($items as $item) {
            $idActividad = isset($item['id_actividad_moodle']) ? $item['id_actividad_moodle'] : 0;
            $nombre      = isset($item['nombre_actividad']) ? $item['nombre_actividad'] : 'Actividad';
            $tipo        = isset($item['tipo_actividad']) ? $item['tipo_actividad'] : 'moodle';
            $porcentaje  = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0;

            $dataInsert = array(
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

            $res = $this->database2->insert('CONFIG_RUBRICA', $dataInsert, array('FECHA_CREACION'));
            if (!$res) {
                $exito = false;
            }
        }

        return $exito;
    }

    /**
     * Obtiene los elementos de la rúbrica configurados para un curso y previo.
     *
     * @return array Lista de objetos con la configuración de la rúbrica
     */
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

    /**
     * Elimina la configuración de una rúbrica para un previo específico.
     *
     * @return bool
     */
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

    /**
     * Guarda el desglose de subnotas calculadas para un estudiante.
     *
     * @param string $codProfesor
     * @param string $codMateria
     * @param string $grupo
     * @param string $semestre
     * @param string $codEstudiante
     * @param string $tipoPrevio
     * @param array  $desgloseSubnotas Array de items con notas originales, porcentaje y subnota calculada
     * @param string $estado          'SUGERIDA' o 'ACEPTADA'
     * @return bool
     */
    public function guardar_subnotas_estudiante($codProfesor, $codMateria, $grupo, $semestre, $codEstudiante, $tipoPrevio, $desgloseSubnotas, $estado = 'SUGERIDA')
    {
        // 1. Eliminar subnotas anteriores del mismo estudiante y previo
        $this->eliminar_subnotas_estudiante($codEstudiante, $codMateria, $grupo, $semestre, $tipoPrevio);

        if (empty($desgloseSubnotas) || !is_array($desgloseSubnotas)) {
            return false;
        }

        $exito = true;
        foreach ($desgloseSubnotas as $item) {
            $idActividad   = isset($item['id_actividad_moodle']) ? $item['id_actividad_moodle'] : 0;
            $nombre        = isset($item['nombre_actividad']) ? $item['nombre_actividad'] : 'Actividad';
            $notaOriginal  = isset($item['nota_original']) ? (float)$item['nota_original'] : 0.0;
            $notaMaxima    = isset($item['nota_maxima']) ? (float)$item['nota_maxima'] : 5.0;
            $porcentaje    = isset($item['porcentaje']) ? (float)$item['porcentaje'] : 0.0;
            $subnota       = isset($item['subnota']) ? (float)$item['subnota'] : 0.0;

            $dataInsert = array(
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

            $res = $this->database2->insert('SUBNOTAS', $dataInsert, array('FECHA_REGISTRO'));
            if (!$res) {
                $exito = false;
            }
        }

        return $exito;
    }

    /**
     * Obtiene el desglose de subnotas de un estudiante para un corte determinado.
     * Esta función alimenta el botón "Ver más" de la vista del estudiante.
     *
     * @return array
     */
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

    /**
     * Elimina las subnotas de un estudiante para un corte específico.
     *
     * @return bool
     */
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

    /**
     * Obtiene el consolidado sugerido de todo el grupo de estudiantes para un previo.
     * Calcula la suma de subnotas (Nota Sugerida Final del previo).
     *
     * @return array
     */
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

    /**
     * Actualiza el estado de las subnotas (ej. marcar como 'ACEPTADA' cuando el docente confirma).
     *
     * @return bool
     */
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

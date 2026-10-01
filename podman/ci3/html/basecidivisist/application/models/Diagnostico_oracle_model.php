<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Diagnostico_oracle_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function _conectar()
    {
        $config = $this->config->item('database2');
        $conexion = @oci_pconnect(
            $config['username'],
            $config['password'],
            $config['hostname'],
            $config['char_set']
        );
        return array('conexion' => $conexion, 'config' => $config);
    }

    public function test_oracle()
    {
        header('Content-Type: application/json; charset=utf-8');

        $res = $this->_conectar();
        $conexion = $res['conexion'];
        $config   = $res['config'];

        if (!$conexion) {
            $error = oci_error();
            echo json_encode(array(
                'conexion' => false,
                'error'    => $error,
                'username_usado' => $config['username'],
            ));
            return;
        }

        $sql = "SELECT table_name FROM user_tables ORDER BY table_name";
        $stid = oci_parse($conexion, $sql);
        $ejecutado = oci_execute($stid);

        if (!$ejecutado) {
            $error = oci_error($stid);
            echo json_encode(array(
                'conexion' => true,
                'consulta_exito' => false,
                'error' => $error,
            ));
            return;
        }

        $tablas = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $tablas[] = $fila;
        }

        echo json_encode(array(
            'conexion' => true,
            'consulta_exito' => true,
            'total_tablas' => count($tablas),
            'tablas' => $tablas,
        ));
    }

    public function resumen_esquemas()
    {
        header('Content-Type: application/json; charset=utf-8');

        $res = $this->_conectar();
        $conexion = $res['conexion'];

        if (!$conexion) {
            echo json_encode(array('error' => oci_error()));
            return;
        }

        $sql = "SELECT owner, COUNT(*) as total_tablas FROM all_tables GROUP BY owner ORDER BY owner";
        $stid = oci_parse($conexion, $sql);
        oci_execute($stid);

        $resumen = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $resumen[] = $fila;
        }

        echo json_encode(array('esquemas' => $resumen));
    }

    public function dump_esquema($owner = null)
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($owner)) {
            echo json_encode(array('error' => 'Falta el owner'));
            return;
        }

        $res = $this->_conectar();
        $conexion = $res['conexion'];

        if (!$conexion) {
            echo json_encode(array('error' => oci_error()));
            return;
        }

        $sql = "SELECT table_name, column_name, data_type, data_length FROM all_tab_columns WHERE owner = :owner ORDER BY table_name, column_id";
        $stid = oci_parse($conexion, $sql);
        oci_bind_by_name($stid, ':owner', $owner);
        oci_execute($stid);

        $estructura = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $tabla = $fila['TABLE_NAME'];
            if (!isset($estructura[$tabla])) {
                $estructura[$tabla] = array();
            }
            $estructura[$tabla][] = array(
                'columna' => $fila['COLUMN_NAME'],
                'tipo'    => $fila['DATA_TYPE'] . '(' . $fila['DATA_LENGTH'] . ')',
            );
        }

        echo json_encode(array('owner' => $owner, 'estructura' => $estructura));
    }

    public function ver_grupo_cargado($cod_profesor = null)
    {
        header('Content-Type: application/json; charset=utf-8');

        $res = $this->_conectar();
        $conexion = $res['conexion'];

        if (!$conexion) {
            echo json_encode(array('error' => oci_error()));
            return;
        }

        if (!empty($cod_profesor)) {
            $sql = "SELECT * FROM SIA.D2_GRUPO_CARGADO WHERE COD_PROFESOR = :cod_profesor AND ROWNUM <= 20";
            $stid = oci_parse($conexion, $sql);
            oci_bind_by_name($stid, ':cod_profesor', $cod_profesor);
        } else {
            $sql = "SELECT * FROM SIA.D2_GRUPO_CARGADO WHERE ROWNUM <= 20";
            $stid = oci_parse($conexion, $sql);
        }

        oci_execute($stid);

        $filas = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $filas[] = $fila;
        }

        echo json_encode(array('total' => count($filas), 'filas' => $filas));
    }
}

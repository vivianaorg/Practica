<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Database2 {

    protected $_CI;
//    private $_connection;
    private $_configs;
    private $_queries;

    public function __construct() {
        $this->_CI = & get_instance();
        $this->_configs = $this->_CI->config->item('database2');
        $this->_queries = array();
    }

    /**
     * Función que retorna un objeto de conexión creado a partir del metodo oci_pconnect que realiza conexiones persistentes.
     * para mayor información remitase a la página oficial de PHP.
     * 
     * http://php.net/manual/es/function.oci-pconnect.php
     * 
     * @return type
     */
    public function get_conn() {
        $conection = oci_pconnect(
                $this->_configs['username'], $this->_configs['password'], $this->_configs['hostname'], $this->_configs['char_set']
        );
        return $conection;
    }

    /**
     * Función que apartir de una cadena SQL realiza una operación en el motor de base de datos y asigna el número de filas retornadas
     * y el contenido como un arreglo asociativo a las variables $nrows y $output respectivamente
     * 
     * @param String $sql - Cadena SQL con la sentencia a ejecutar (generalmente select)
     * @param int $nrows - Número de filas retornadas (recibe la referencia a la variable)
     * @param array $output - 
     * 
     * Contenido de las filas retornadas en formato de array asociativo.
     * Ejemplo:
     * 
     * SQL: select codigo,nombre from tabla_usuario
     * la función trae dos registros y asigna las variables:
     * $nrows = 2
     * $output = array("CODIGO" => array("1234","1235"), "NOMBRE" => array("Griselda","Cecilia"))
     */
    public function get_sql($sql, &$nrows, &$output) {
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        oci_execute($stid);
        $nrows = oci_fetch_all($stid, $output);
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'), $nrows);
    }

    /**
     * Función que retorna dado una cadena SQL de la cual se espera un solo registro, asigna un objeto
     * que tiene por atributos la información esperada de la consulta en la variable $object.
     * 
     * @param String $sql
     * @param Object $object
     * 
     * Ejemplo: 
     * 
     * SQL: "select codigo,nombre from tabla_usuario"
     * La función asigna el objeto y sus atributos serán accesibles de la siguiente manera:
     * $object->NOMBRE = "Griselda"
     * $object->CODIGO = "1234"
     */
    public function get_sql_object($sql, &$object) {
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        oci_execute($stid);
        $object = oci_fetch_object($stid);
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'));
    }

    /**
     * Función que retorna dado una cadena SQL de la cual se esperan varios registro, asigna un arreglo de objetos
     * que tiene por atributos la información esperada de la consulta.
     * 
     * @param String $sql
     * @param array $array
     */
    public function get_obj_array($sql, &$array) {
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        oci_execute($stid);
        $array = array();
        while (($row = oci_fetch_object($stid)) != NULL) {
            array_push($array, $row);
        }
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'));
    }

    /**
     * Función que ejecuta una sentencia SQL y retorna TRUE si se ejecuto correctamente o FALSE de lo contrario.
     * 
     * @param String $sql
     * @return boolean
     */
    public function get_sql_bool($sql) {
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        $result = oci_execute($stid);
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'));
        return $result;
    }

    /**
     * Funcion que permite insertar un registro en una tabla a partir de una cadena con su nombre y un arreglo con la información
     * a insertar de forma asociativa.
     * 
     * @param String $table - Nombre de la tabla (Ejemplos: SIA.ALUMNO, MATRICULA.USUARIO, NOMBRE_TABLA)
     * @param array $info - Información del registro a insertar en la tabla (Ejemplo: array("NOMBRE"=> "Griselda", "Código" => 1234))
     * @param array $fechas - Arreglo con los campos del arreglo $info que equivalen a fechas, estos seran procesados con TO_DATE() o seran ajustados si contienen como valor SYSDATE.
     * @return boolean - TRUE si se realizo la inserción con éxito o FALSE de lo contrario.
     */
    public function insert($table, $info, $fechas = array()) {
        $campos = " ";
        $values = " ";
        $last_key = key(array_slice($info, -1, 1, TRUE));
        foreach ($info as $key => $value) {
            $campos.= $key . (($key == $last_key) ? "" : ",");
            if (in_array($key, $fechas)) {
                if ($value == 'SYSDATE') {
                    $values.= "SYSDATE" . (($key == $last_key) ? "" : ",");
                } else {
                    $values.= "TO_DATE('{$value}','DD-MM-YYYY')" . (($key == $last_key) ? "" : ",");
                }
            } else {
                $values.= "'" . $value . (($key == $last_key) ? "'" : "',");
            }
        }
        $sql = "INSERT INTO {$table}({$campos}) VALUES({$values})";
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        $result = oci_execute($stid);
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'));
        return $result;
    }

    /**
     * Función que permite actualizar un registro de una tabla
     * 
     * @param String $table - Nombre de la tabla
     * @param array $set - arreglo de campos a modificar
     * @param array $where - arreglo de campos a comparar en la tabla para hacer la actualización
     * @param array $fechas - Arreglo con los campos del arreglo $info que equivalen a fechas, estos seran procesados con TO_DATE() o seran ajustados si contienen como valor SYSDATE.
     * @return boolean
     */
    public function update($table, $set, $where, $fechas = array()) {
        $sql_set = "";
        $last_key_set = key(array_slice($set, -1, 1, TRUE));
        foreach ($set as $key => $value) {
            if (in_array($key, $fechas)) {
                if ($value == 'SYSDATE') {
                    $sql_set.= $key . "=SYSDATE" . (($key == $last_key_set) ? "" : ",");
                } else {
                    $sql_set.= $key . "=TO_DATE('{$value}','DD-MM-YYYY HH24:MI:SS')" . (($key == $last_key_set) ? "" : ",");
                }
            } else {
                $sql_set.= $key . "='" . $value . (($key == $last_key_set) ? "'" : "',");
            }
        }
        $sql_where = "";
        $last_key_where = key(array_slice($where, -1, 1, TRUE));
        foreach ($where as $key => $value) {
            if (in_array($key, $fechas)) {
                if ($value == 'SYSDATE' || $value == 'TRUNC(SYSDATE)') {
                    $sql_where .= $key . "=" . $value . (($key == $last_key_where) ? "" : " AND ");
                } else {
                    $sql_where.= $key . "=TO_DATE('{$value}','DD-MM-YYYY HH24:MI:SS')" . (($key == $last_key_where) ? "" : " AND ");
                }
            } else {
                $sql_where.= $key . "='" . $value . (($key == $last_key_where) ? "'" : "' AND ");
            }
        }
        $sql = "UPDATE {$table} SET {$sql_set} WHERE {$sql_where}";
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        $result = oci_execute($stid);
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'));
        return $result;
    }

    /**
     * Función que permite borrar registros de una tabla.
     * 
     * @param String $table - Nombre de la tabla
     * @param array $where - Arreglo de campos a comparar para hacer la eliminación
     * @return boolean - TRUE si se elimina con éxito o FALSE  de lo contrario.
     */
    public function delete($table, $where) {
        $sql_where = "";
        $last_key_where = key(array_slice($where, -1, 1, TRUE));
        foreach ($where as $key => $value) {
            $sql_where.= $key . "='" . $value . (($key == $last_key_where) ? "'" : "' AND ");
        }
        $sql = "DELETE FROM {$table} WHERE {$sql_where}";
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        $result = oci_execute($stid);
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'));
        return $result;
    }

    /**
     * Función que ejecuta un procedimiento almacenado
     * 
     * @param String $procedure - procedimiento a ejecutar (Ejemplo: "ACTUALIZACION_DATOS(PARAMETRO1,PARAMETRO2)")
     * @return boolean - TRUE si el procedimiento se ejecuta sin errores o FALSE  de lo contrario
     */
    public function execute($procedure) {
        $sql = "BEGIN {$procedure}; END;";
        $this->_CI->benchmark->mark('time_begin');
        $conection = $this->get_conn();
        $stid = oci_parse($conection, $sql);
        $result = oci_execute($stid);
        oci_close($conection);
        oci_free_statement($stid);
        $this->_CI->benchmark->mark('time_end');
        $this->add_query($sql, $this->_CI->benchmark->elapsed_time('time_begin', 'time_end'));
        return $result;
    }

    //Funciones utilizadas para el seguimiento de las consultas en el profiler
    
    public function add_query($query, $query_time = 0, $nrows = 0) {
        $this->_queries[] = $query;
        $this->_CI->db->queries[] = $query;
        $this->_CI->db->query_times[] = $query_time;
        $this->_CI->session->userdata['queries'][] = $query;
        $this->_CI->session->userdata['query_time'][] = $query_time;
        $this->_CI->session->userdata['query_nrows'][] = $nrows;
    }

    function get_queries() {
        return $this->_queries;
    }

}

/* End of file Database2.php */
/* Location: ./application/libraries/Database2.php */
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Materia_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    public function materiasMatriculadasPensum($codigo) {
        $nrows = 0;
        $output = array();
        
        $sql = "select * from matricula.materiasMatriculadasPensum where COD_ALUMNO=SUBSTR('{$codigo}' ,'4','4') and 
        COD_CARRERA=SUBSTR('{$codigo}' ,'1','3')";

        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }

        return $output;
    }

    public function codigo_proyecto($cod_carrera, $cod_alumno) {
        $obj = null;
        $sql = "select p.cod_materia codigo_proyecto "
            . "from sia.alumno a, sia.pensum p "
            . "where a.cod_carrera = p.cod_carrera and "
            . "a.pensum = p.tipo_pensum and "
            . "a.cod_carrera = '{$cod_carrera}' and "
            . "a.cod_alumno = '{$cod_alumno}'";

        if (isset($this->database2)) {
            $this->database2->get_sql_object($sql, $obj);
        }
        return $obj;
    }
}

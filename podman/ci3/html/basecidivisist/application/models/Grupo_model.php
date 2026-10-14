<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Grupo_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    public function gruposAsignados($user) {
        $nrows = 0;
        $output = array();
        $sql = "SELECT * FROM SIA.D2_GRUPO_CARGADO WHERE COD_PROFESOR='{$user}' ORDER BY 9, 1, 2";
        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }
        return $output;
    }

    public function gruposAsignadosTipo($user, $tipo, $seccional) {
        $nrows = 0;
        $output = array();
        $extra = ($tipo == 'VA')
            ? (' and COD_CARRERA||COD_MATERIA||GRUPO IN (SELECT COD_CARRERA||COD_MATERIA||GRUPO FROM SIA.GRUPO_VAC) ')
            : (' and COD_CARRERA||COD_MATERIA||GRUPO NOT IN (SELECT COD_CARRERA||COD_MATERIA||GRUPO FROM SIA.GRUPO_VAC) ');
        $tipoReal = ($tipo == 'VA') ? 'P' : $tipo;

        $sql = "SELECT * FROM SIA.D2_GRUPO_CARGADO WHERE COD_PROFESOR='{$user}' AND TIPO_CARRERA = '{$tipoReal}' AND SECCIONAL = '{$seccional}' " . $extra . " ORDER BY 9, 1, 2";
        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }
        return $output;
    }

    public function getGrupoInclusiones($codigo, $carrera, $materia, $grupo, $seccional) {
        $nrows = 0;
        $output = array();
        $sql = "SELECT "
            . "T.TEXTO, T.TIPO, I.VALIDADO, I.EDITADO, ORDEN, T.DESCRIPCION, "
            . "'Habilitado: '||TO_CHAR(FECHA_INI,'MON DD HH24:MI')||' al '||TO_CHAR(FECHA_FIN,'MON DD HH24:MI') LET, "
            . "TO_CHAR(FECHA_VALIDA,'DD-MM-YYYY HH24:MI') FECHAVALIDA "
            . "FROM SIA.TIPO_INCLUSION T, SIA.GRUPO_INCLUSION I, SIA.GRUPOC G "
            . "WHERE T.TIPO=I.TIPO AND I.COD_CARRERA=G.COD_CARRERA AND "
            . "I.COD_MATERIA=G.COD_MATERIA AND I.GRUPO=G.GRUPO AND "
            . "I.SECCIONAL=G.SECCIONAL AND G.COD_PROFESOR='{$codigo}' "
            . "AND I.COD_CARRERA = '{$carrera}' "
            . "AND I.COD_MATERIA = '{$materia}' "
            . "AND I.GRUPO = '{$grupo}' "
            . "AND I.SECCIONAL = '{$seccional}' AND "
            . "SYSDATE BETWEEN FECHA_INI AND FECHA_FIN "
            . "ORDER BY ORDEN";

        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }
        return $output;
    }
}

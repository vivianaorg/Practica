<?php

class Cambio_clave_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    public function insertRecuperarClave($info) {
        return $this->database2->insert('MATRICULA.DIVISIST2_CAMBIO_CLAVE', $info);
    }

    public function verificarRecuperarClave($codCarrera, $codAlumno, $llave) {
        $this->database2->get_sql_object(
                "SELECT * FROM MATRICULA.DIVISIST2_CAMBIO_CLAVE "
                . "WHERE COD_CARRERA = '{$codCarrera}' "
                . "AND COD_ALUMNO = '{$codAlumno}' "
                . "AND LLAVE = '{$llave}' "
                . "ORDER BY FECHA_REGISTRO DESC", $obj);
        return $obj;
    }

    /**
     * Verifica que exista un registro y que su tiempo de generación sea superior a una hora
     * @param type $codigo
     * @return type
     */
    public function verificarTiempoRC($codCarrera, $codAlumno) {
        $this->database2->get_sql_object(
                "SELECT 'X' FROM MATRICULA.DIVISIST2_CAMBIO_CLAVE "
                . "WHERE COD_CARRERA = '{$codCarrera}' "
                . "AND COD_ALUMNO = '{$codAlumno}' "
                . "AND FECHA_REGISTRO > SYSDATE - (1/24) ", $obj);
        return $obj;
    }

    public function activarRecuperarClave($codCarrera, $codAlumno, $llave) {
        $set = array(
            'ACTIVE' => '1'
        );
        $where = array(
            'COD_CARRERA' => $codCarrera,
            'COD_ALUMNO' => $codAlumno,
            'LLAVE' => $llave
        );
        return $this->database2->update('MATRICULA.DIVISIST2_CAMBIO_CLAVE', $set, $where);
    }

    public function actualizarClave($codCarrera, $codAlumno, $clavehex, $llave) {
        $this->database2->execute("matricula.ds2actualizarclavealumno('{$codCarrera}','{$codAlumno}','{$clavehex}','{$llave}')");
    }

}

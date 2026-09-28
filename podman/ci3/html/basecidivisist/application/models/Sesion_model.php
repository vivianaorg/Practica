<?php

class Sesion_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    public function registrarSesion($info) {
        return $this->database2->insert('SESION_USUARIO', $info);
    }

    public function getRegistros($codigoAlumno) {
        $sql = "select * from matricula.divisist2_sesion_alumno where cod_carrera||cod_alumno = '{$codigoAlumno}'";
        $this->database2->get_obj_array($sql, $arrayObj);
        return $arrayObj;
    }

    public function validarSesionExterna($codCarrera, $codAlumno, $sessExpiration) {
        $sql = "select matricula.ds2validarsesionexterna('{$codCarrera}','{$codAlumno}',{$sessExpiration}) validarsesionexterna from dual";
        $this->database2->get_sql_object($sql, $obj);
        return $obj->VALIDARSESIONEXTERNA == 1 ? TRUE : FALSE;
    }

    public function intentosFallidos($codCarrera, $codAlumno) {
        $sql = "select matricula.ds2intentosfallidos('{$codCarrera}','{$codAlumno}') intentos_fallidos from dual";
        $this->database2->get_sql_object($sql, $obj);
        return $obj ? $obj->INTENTOS_FALLIDOS : FALSE;
    }

    public function get_datos($user) {
        return (object) array("CODIGO" => $user, "NOMBRES" => "Griselda", "NOMBRE_COMPLETO" => "Griselda María");
    }

    public function validar_password() {
        RETURN TRUE;
        $codCarrera = substr($user, 0, 3);
        $codAlumno = substr($user, 3);
        $this->database2->get_sql_object("select ds2validarclavealumno('{$codCarrera}','{$codAlumno}','{$pass}') valido from dual", $obj);
        return $obj ? ($obj->VALIDO == 1 ? TRUE : FALSE) : FALSE;
    }

}

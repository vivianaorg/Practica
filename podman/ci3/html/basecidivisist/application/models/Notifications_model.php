<?php

class Notifications_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    public function get_notifications($codigo) {
        $this->database2->get_obj_array("select rownum,d.* from divisist2_notificacion_view d where codigo_receptor = '{$codigo}'", $arr);
        return $arr;
    }

    public function unreaded_notifications($codigo) {
        $sql = "select rownum,d.* from divisist2_notificacion_view d where codigo_receptor = '{$codigo}' and estado = 0";
        $this->database2->get_obj_array($sql, $arr);
        return $arr;
    }

    public function read_notifications($codigo) {
        return $this->database2->update('DIVISIST2_NOTIFICACION', array('ESTADO' => '1', 'FECHA_LECTURA' => 'SYSDATE'), ARRAY('CODIGO_RECEPTOR' => $codigo, 'ESTADO' => '0'), ARRAY('FECHA_LECTURA'));
    }

    public function addNotification($info) {
        return $this->database2->insert('MATRICULA.DIVISIST2_NOTIFICACION', $info);
    }

}

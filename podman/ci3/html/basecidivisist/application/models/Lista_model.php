<?php

class Lista_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    public function get_lista() {
        $sql = "select * from lista";
        $this->database2->get_obj_array($sql, $arr);
        return $arr;
    }

}

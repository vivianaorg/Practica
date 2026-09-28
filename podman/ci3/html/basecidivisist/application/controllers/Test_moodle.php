<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test_moodle extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Moodle_model');
    }

    public function importar($materiaId = null)
    {
        if (empty($materiaId)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Falta materia_id en la URL']);
            return;
        }

        $resultado = $this->Moodle_model->importar_materia($materiaId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado, JSON_PRETTY_PRINT);
    }

    public function actividades($courseId = null)
    {
        if (empty($courseId)) {
            echo "Falta el course_id en la URL. Ejemplo: /test_moodle/actividades/2";
            return;
        }

        $resultado = $this->Moodle_model->listar_actividades($courseId);

        $data['exito']       = $resultado['exito'] ?? false;
        $data['mensaje']     = $resultado['mensaje'] ?? null;
        $data['curso_nombre']= $resultado['datos']['curso_nombre'] ?? null;
        $data['actividades'] = $resultado['datos']['actividades'] ?? [];

        $this->load->view('dashboard/actividades_moodle', $data);
    }
}
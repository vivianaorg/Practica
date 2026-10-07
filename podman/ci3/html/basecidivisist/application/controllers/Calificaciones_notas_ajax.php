<?php

class Calificaciones_notas_ajax extends CMS_Controller {

    function __construct() {
        parent::__construct();

        if (!isset($this->usuario)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(array('exito' => false, 'mensaje' => 'Sesión expirada. Por favor, inicie sesión nuevamente.'));
                exit;
            } else {
                redirect();
            }
        }
        
        $this->load->helper('profesor');
    }

    public function guardar_calificaciones_corte_ajax() {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('Subnotas_model');

        $codProfesor = $this->input->post('cod_profesor');
        $codMateria  = $this->input->post('cod_materia');
        $grupo       = $this->input->post('grupo');
        $semestre    = $this->input->post('semestre');
        $tipoPrevio  = $this->input->post('tipo_previo');
        $datosRaw    = $this->input->post('calificaciones');

        if (empty($codProfesor) || empty($codMateria) || empty($tipoPrevio)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Parametros requeridos incompletos'));
            return;
        }

        $grupo = normalizar_grupo($grupo);

        if ($this->Subnotas_model->corte_esta_calificado($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Este corte ya fue calificado y cerrado.'));
            return;
        }

        $estudiantes = is_array($datosRaw) ? $datosRaw : json_decode($datosRaw, true);
        if (!is_array($estudiantes) || empty($estudiantes)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'No se recibieron datos de estudiantes'));
            return;
        }

        $guardado = $this->Subnotas_model->guardar_calificaciones_grupo(
            $codProfesor,
            $codMateria,
            $grupo,
            $semestre,
            $tipoPrevio,
            $estudiantes
        );

        if ($guardado) {
            echo json_encode(array('exito' => true, 'mensaje' => 'Calificaciones del corte guardadas exitosamente'));
        } else {
            $detalle = isset($this->Subnotas_model->ultimo_error) ? $this->Subnotas_model->ultimo_error : '';
            echo json_encode(array(
                'exito'   => false,
                'mensaje' => 'Error al guardar en base de datos' . ($detalle ? ': ' . $detalle : '')
            ));
        }
    }
}

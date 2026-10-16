<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Informacion_academica extends CMS_Controller {

    public $estudiante;

    public function __construct() {
        parent::__construct();

        // 1. Cargar helpers necesarios
        $this->load->helper(array('form', 'url'));

        // 2. Resolver estudiante en sesión (con fallback para pruebas sin sesión)
        $this->estudiante = $this->session->userdata('estudiante_divisist');
        if (!$this->estudiante) {
            if (isset($this->usuario)) {
                $this->estudiante = $this->usuario;
            } else {
                $this->estudiante = (object) array(
                    'CODIGO'      => '1151234',
                    'COD_CARRERA' => '115',
                    'COD_ALUMNO'  => '1234',
                    'NOMBRES'     => 'Estudiante',
                    'APELLIDOS'   => 'Pruebas'
                );
            }
        }

        if (!isset($this->usuario)) {
            $this->usuario = $this->estudiante;
            $this->template->set('informacion_usuario', $this->usuario);
        }

        $this->load->model('Materia_model', 'materia_model');
        $this->load->model('Subnotas_model');

        // 4. Configurar plantilla
        $this->template->set_template('default_template/default_template');
        $this->template->add_css('css/adminlte/skins/skin-red-light.min');
        $this->template->add_css('css/adminlte/skins/_all-skins.min');
    }

    public function index($codigo = null) {
        $this->materias($codigo);
    }

    public function materias($codigo = null) {
        if ($codigo !== null && trim($codigo) !== '') {
            $codigo = trim($codigo);
            $this->estudiante = (object) array(
                'CODIGO'      => $codigo,
                'COD_CARRERA' => substr($codigo, 0, 3),
                'COD_ALUMNO'  => substr($codigo, 3, 4),
                'NOMBRES'     => 'Estudiante',
                'APELLIDOS'   => $codigo
            );
            $this->session->set_userdata('estudiante_divisist', $this->estudiante);
            $this->usuario = $this->estudiante;
            $this->template->set('informacion_usuario', $this->usuario);
        }
        $materiaspresenciales = $this->materia_model->materiasMatriculadasPensum($this->estudiante->CODIGO);
        $codigo_proyecto = $this->materia_model->codigo_proyecto($this->estudiante->COD_CARRERA, $this->estudiante->COD_ALUMNO);

        if ($codigo_proyecto && isset($codigo_proyecto->CODIGO_PROYECTO)) {
            $this->template->set('codigo_proyecto', $codigo_proyecto->CODIGO_PROYECTO);
        }

        if (!empty($materiaspresenciales['NOMBREMAT'])) {
            $this->template->set('materiasmatriculadas', $materiaspresenciales);
            $this->template->set('tiponotamateria', $this->_tipoNotaMateria($materiaspresenciales));
        }

        $this->template->set('carrera', isset($this->estudiante->COD_CARRERA) ? $this->estudiante->COD_CARRERA : '');
        $this->template->set('secundaria', true);
        $this->template->set('item_sidebar_active', 'materias');
        $this->template->set('content_header', 'Información Académica');
        $this->template->set('content_sub_header', 'Consulte sus materias matriculadas, materias no matriculadas, cursos de formación y módulos');
        $this->template->render('informacion_academica/materias');
    }

    /**
     * Mapea la matriz de tipos de notas y estados por corte para cada materia
     */
    private function _tipoNotaMateria($materias) {
        $tipoNotas = array();
        $numFilas = isset($materias['CODCARMAT']) ? count($materias['CODCARMAT']) : 0;

        for ($i = 0; $i < $numFilas; $i++) {
            $tipoNotas[$i] = array(
                'UNICANOTA' => array('N', 'N', 'N', 'N', 'N', 'N'),
                'TIPO'      => array('NOTA1', 'NOTA2', 'NOTA3', 'EXA', 'HAB', 'OPC'),
                'EST'       => array(
                    isset($materias['EST_NOTA1'][$i]) ? $materias['EST_NOTA1'][$i] : 'V',
                    isset($materias['EST_NOTA2'][$i]) ? $materias['EST_NOTA2'][$i] : 'V',
                    isset($materias['EST_NOTA3'][$i]) ? $materias['EST_NOTA3'][$i] : 'V',
                    isset($materias['EST_EXA'][$i])   ? $materias['EST_EXA'][$i]   : 'V',
                    isset($materias['EST_HAB'][$i])   ? $materias['EST_HAB'][$i]   : 'V',
                    isset($materias['EST_OPC'][$i])   ? $materias['EST_OPC'][$i]   : 'V'
                )
            );
        }

        return $tipoNotas;
    }

    /**
     * Endpoint AJAX para consultar las subnotas y actividades de la rúbrica del estudiante
     */
    public function obtener_subnotas_rubrica() {
        header('Content-Type: application/json; charset=utf-8');

        $codMateria  = $this->input->get_post('cod_materia');
        $grupo       = $this->input->get_post('grupo');
        $tipoPrevio  = $this->input->get_post('tipo_previo');
        $semestre    = $this->input->get_post('semestre');

        if (empty($semestre)) {
            $this->load->helper('profesor');
            $semestre = function_exists('obtener_semestre_actual') ? obtener_semestre_actual() : '2026-2';
        }

        // Mapear tipos de previos a los identificadores usados en CONFIG_RUBRICA / SUBNOTAS
        $mapaPrevios = array(
            'NOTA1' => '1', '1P' => '1', '1' => '1',
            'NOTA2' => '2', '2P' => '2', '2' => '2',
            'NOTA3' => '3', '3P' => '3', '3' => '3',
            'EXA'   => 'FINAL', 'EX' => 'FINAL', 'FINAL' => 'FINAL'
        );

        $previoId = isset($mapaPrevios[$tipoPrevio]) ? $mapaPrevios[$tipoPrevio] : $tipoPrevio;
        $codEstudiante = $this->input->get_post('cod_estudiante');
        if (empty($codEstudiante)) {
            $codEstudiante = isset($this->estudiante->CODIGO) ? $this->estudiante->CODIGO : '';
        }

        $subnotas = $this->Subnotas_model->obtener_subnotas_estudiante($codEstudiante, $codMateria, $grupo, $semestre, $previoId);

        // Si no existen subnotas en Oracle para este corte y materia, consultar dinámicamente en Moodle
        if (empty($subnotas)) {
            try {
                $this->load->model('Moodle_model');
                $resMoodle = $this->Moodle_model->obtener_cursos_estudiante($codEstudiante);
                if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['cursos']) && is_array($resMoodle['datos']['cursos'])) {
                    foreach ($resMoodle['datos']['cursos'] as $curso) {
                        $cShort = isset($curso['shortname']) ? trim($curso['shortname']) : '';
                        $cId    = isset($curso['id']) ? (string)$curso['id'] : '';
                        $match  = false;

                        if ($cShort === $codMateria || $cId === (string)$codMateria) {
                            $match = true;
                        } elseif (stripos($codMateria, $cShort) !== false || stripos($cShort, $codMateria) !== false) {
                            $match = true;
                        }

                        if ($match && !empty($curso['actividades']) && is_array($curso['actividades'])) {
                            $totalActs = count($curso['actividades']);
                            $porcEach  = $totalActs > 0 ? round(100.0 / $totalActs, 1) : 0;

                            foreach ($curso['actividades'] as $idx => $act) {
                                $notaOrig = isset($act['nota_final']) ? floatval($act['nota_final']) : 0.0;
                                $notaMax  = isset($act['nota_maxima']) ? floatval($act['nota_maxima']) : 5.0;
                                $subnota  = round(($notaOrig * $porcEach) / 100.0, 2);

                                $subnotas[] = (object) array(
                                    'ID'                  => isset($act['grade_item_id']) ? $act['grade_item_id'] : ($idx + 1),
                                    'COD_ESTUDIANTE'      => $codEstudiante,
                                    'ID_ACTIVIDAD_MOODLE' => isset($act['id_actividad']) ? $act['id_actividad'] : 0,
                                    'NOMBRE_ACTIVIDAD'    => isset($act['nombre_actividad']) ? $act['nombre_actividad'] : 'Actividad Moodle',
                                    'NOTA_ORIGINAL'       => $notaOrig,
                                    'NOTA_MAXIMA'         => $notaMax,
                                    'PORCENTAJE'          => $porcEach,
                                    'SUBNOTA'             => $subnota,
                                    'ESTADO'              => 'MOODLE',
                                    'FECHA_REGISTRO'      => date('Y-m-d H:i:s')
                                );
                            }
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                log_message('error', 'obtener_subnotas_rubrica Moodle exception: ' . $e->getMessage());
            }
        }

        echo json_encode(array(
            'exito'    => true,
            'subnotas' => $subnotas,
            'total'    => count($subnotas)
        ));
    }
}

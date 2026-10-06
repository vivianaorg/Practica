<?php

class Dashboard extends CMS_Controller {

    function __construct() {
        parent::__construct();

        if (!isset($this->usuario)) {
            redirect();
        }

        $this->template->set_template('default_template/default_template');
        $this->template->add_css('css/adminlte/skins/skin-red-light.min');
        $this->template->add_css('css/adminlte/skins/_all-skins.min');
    }

    public function index() {
        $this->load->model('lista_model');
        $this->template->set('item_sidebar_active', 'dashboard');
        $this->template->set('content_header', 'Dashboard (Titulo de la sección)');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/index');
    }

    public function item2() {
        $this->template->add_message(array("error" => "ocurrio un error"));
        $this->template->add_message(array("warning" => "ocurrio una alerta"));
        $this->template->add_message(array("success" => "ocurrio algo sin problemas"));
        $this->template->add_message(array("info" => "mensaje de información"));

        $this->template->set_flash_message(array("mensaje de información cargado en la página anterior por el controlador item2"), "info");

        $this->template->set('item_sidebar_active', 'nevegacion2');
        $this->template->set('content_header', 'ITEM 2 - Ejemplos de alertas');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/item2');
    }

    public function item3() {
        $this->template->set('item_sidebar_active', 'nevegacion3');
        $this->template->set('content_header', 'Ejemplo de Adminlte');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/item3');
    }

    public function item4() {
        echo 'hola';
        $this->template->set('item_sidebar_active', 'nevegacion4');
        $this->template->set('content_header', 'Ejemplo de Bootstrap');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/item4');
    }

    public function listar_cursos_rubricas_ajax() {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('Subnotas_model');
        $this->load->model('Moodle_model');
        $this->load->model('Calculo_notas_model');

        $codProfesor = (isset($this->usuario) && isset($this->usuario->CODIGO)) ? $this->usuario->CODIGO : '04608';
        if ($this->input->post('cod_profesor')) {
            $codProfesor = $this->input->post('cod_profesor');
        } elseif ($this->input->get('cod_profesor')) {
            $codProfesor = $this->input->get('cod_profesor');
        }

        $registros = $this->Subnotas_model->obtener_cursos_con_rubricas($codProfesor);

        // Mapear nombres amigables desde Moodle si están disponibles
        $nombresMoodle = array();
        $cursosMoodleRes = $this->Moodle_model->listar_cursos();
        if (isset($cursosMoodleRes['datos']['cursos']) && is_array($cursosMoodleRes['datos']['cursos'])) {
            foreach ($cursosMoodleRes['datos']['cursos'] as $cm) {
                $codigo = isset($cm['codigo']) ? $cm['codigo'] : '';
                $nombre = isset($cm['nombre']) ? $cm['nombre'] : '';
                $idCur  = isset($cm['id']) ? $cm['id'] : null;
                $parsed = $this->Calculo_notas_model->parsear_curso_codigo($codigo, $idCur);
                $k = $parsed['cod_materia'] . '|' . $parsed['grupo'];
                $nombresMoodle[$k] = $nombre;
            }
        }

        // Agrupar los registros por curso (COD_MATERIA, GRUPO, SEMESTRE)
        $cursos = array();
        if (!empty($registros) && is_array($registros)) {
            foreach ($registros as $reg) {
                $codMateria = isset($reg->COD_MATERIA) ? trim($reg->COD_MATERIA) : '';
                $grupo      = isset($reg->GRUPO) ? trim($reg->GRUPO) : '-';
                $semestre   = isset($reg->SEMESTRE) ? trim($reg->SEMESTRE) : '';
                $tipoPrevio = isset($reg->TIPO_PREVIO) ? trim($reg->TIPO_PREVIO) : '';
                $totalItems = isset($reg->TOTAL_ITEMS) ? (int)$reg->TOTAL_ITEMS : 0;

                $clave = $codMateria . '|' . $grupo . '|' . $semestre;

                if (!isset($cursos[$clave])) {
                    $kMoodle = $codMateria . '|' . $grupo;
                    $nombreMoodle = isset($nombresMoodle[$kMoodle]) ? $nombresMoodle[$kMoodle] : null;

                    if ($nombreMoodle) {
                        $etiqueta = $nombreMoodle . ' (' . $codMateria . ($grupo !== '-' ? '-' . $grupo : '') . ') [' . $semestre . ']';
                    } else {
                        $etiqueta = 'Materia ' . $codMateria . ($grupo !== '-' ? ' - Gr. ' . $grupo : '') . ' (' . $semestre . ')';
                    }

                    $cursos[$clave] = array(
                        'clave'          => $clave,
                        'cod_materia'    => $codMateria,
                        'grupo'          => $grupo,
                        'semestre'       => $semestre,
                        'nombre_materia' => $nombreMoodle ? $nombreMoodle : $codMateria,
                        'etiqueta'       => $etiqueta,
                        'cortes'         => array(),
                    );
                }

                if (!empty($tipoPrevio)) {
                    $cursos[$clave]['cortes'][] = array(
                        'tipo_previo' => $tipoPrevio,
                        'total_items' => $totalItems,
                    );
                }
            }
        }

        echo json_encode(array(
            'exito'  => true,
            'cursos' => array_values($cursos)
        ));
    }

    public function obtener_items_rubrica_ajax() {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('Subnotas_model');

        $codProfesor = (isset($this->usuario) && isset($this->usuario->CODIGO)) ? $this->usuario->CODIGO : '04608';
        if ($this->input->post('cod_profesor')) {
            $codProfesor = $this->input->post('cod_profesor');
        } elseif ($this->input->get('cod_profesor')) {
            $codProfesor = $this->input->get('cod_profesor');
        }

        $codMateria = $this->input->post('cod_materia') ? $this->input->post('cod_materia') : $this->input->get('cod_materia');
        $grupo      = $this->input->post('grupo') !== null ? $this->input->post('grupo') : $this->input->get('grupo');
        $semestre   = $this->input->post('semestre') ? $this->input->post('semestre') : $this->input->get('semestre');
        $tipoPrevio = $this->input->post('tipo_previo') ? $this->input->post('tipo_previo') : $this->input->get('tipo_previo');

        if (empty($codMateria) || empty($tipoPrevio)) {
            echo json_encode(array(
                'exito'   => false,
                'mensaje' => 'Parámetros incompletos para consultar la rúbrica'
            ));
            return;
        }

        $grupo = (!empty($grupo) && $grupo !== '') ? $grupo : '-';

        $items = $this->Subnotas_model->obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        $resultado = array();
        if (!empty($items) && is_array($items)) {
            foreach ($items as $it) {
                $resultado[] = array(
                    'id_actividad_moodle' => isset($it->ID_ACTIVIDAD_MOODLE) ? (int)$it->ID_ACTIVIDAD_MOODLE : 0,
                    'nombre_actividad'    => isset($it->NOMBRE_ACTIVIDAD) ? $it->NOMBRE_ACTIVIDAD : '',
                    'tipo_actividad'      => isset($it->TIPO_ACTIVIDAD) ? $it->TIPO_ACTIVIDAD : '',
                    'porcentaje'          => isset($it->PORCENTAJE) ? (float)$it->PORCENTAJE : 0,
                );
            }
        }

        echo json_encode(array(
            'exito' => true,
            'items' => $resultado,
            'total' => count($resultado)
        ));
    }
}

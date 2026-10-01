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

    public function actividades_moodle($courseId = 2) {
        $this->load->model('Moodle_model');
        $resultado = $this->Moodle_model->listar_actividades($courseId);

        $exito = isset($resultado['exito']) ? $resultado['exito'] : false;
        $mensaje_moodle = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $curso_nombre = isset($resultado['datos']['curso_nombre']) ? $resultado['datos']['curso_nombre'] : null;
        $actividades = isset($resultado['datos']['actividades']) ? $resultado['datos']['actividades'] : array();

        $this->template->set('exito', $exito);
        $this->template->set('mensaje_moodle', $mensaje_moodle);
        $this->template->set('course_id', $courseId);
        $this->template->set('curso_nombre', $curso_nombre);
        $this->template->set('actividades', $actividades);

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Actividades de Moodle');
        $this->template->set('content_sub_header', '');
        $this->template->render('dashboard/actividades_moodle');
    }

    public function cursos_moodle() {
        $this->load->model('Moodle_model');
        $resultado = $this->Moodle_model->listar_cursos();

        $exito = isset($resultado['exito']) ? $resultado['exito'] : false;
        $mensaje_moodle = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $cursos = isset($resultado['datos']['cursos']) ? $resultado['datos']['cursos'] : array();

        $this->template->set('exito', $exito);
        $this->template->set('mensaje_moodle', $mensaje_moodle);
        $this->template->set('cursos', $cursos);

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Cursos de Moodle');
        $this->template->set('content_sub_header', 'Selecciona un curso para ver sus actividades');
        $this->template->render('dashboard/cursos_moodle');
    }

    public function configurar_rubrica($courseId = 2, $tipoPrevio = '1') {
        $this->load->model('Moodle_model');
        $this->load->model('Subnotas_model');
        $this->load->model('Calculo_notas_model');

        $codProfesor = (isset($this->usuario) && isset($this->usuario->CODIGO)) ? $this->usuario->CODIGO : '04608';
        $semestre    = date('Y') . '-' . (date('n') <= 6 ? '1' : '2');

        $cursoCodigo = '';
        $cursosRes = $this->Moodle_model->listar_cursos();
        if (isset($cursosRes['datos']['cursos']) && is_array($cursosRes['datos']['cursos'])) {
            foreach ($cursosRes['datos']['cursos'] as $c) {
                if ($c['id'] == $courseId) {
                    $cursoCodigo = isset($c['codigo']) ? $c['codigo'] : '';
                    break;
                }
            }
        }

        $infoCurso  = $this->Calculo_notas_model->parsear_curso_codigo($cursoCodigo);
        $codMateria = $infoCurso['cod_materia'];
        $grupo      = $infoCurso['grupo'];

        $resultado = $this->Moodle_model->listar_actividades($courseId);
        $exito = isset($resultado['exito']) ? $resultado['exito'] : false;
        $mensaje_moodle = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $curso_nombre = isset($resultado['datos']['curso_nombre']) ? $resultado['datos']['curso_nombre'] : null;
        $actividades = isset($resultado['datos']['actividades']) ? $resultado['datos']['actividades'] : array();

        $rubricaActual = $this->Subnotas_model->obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
        $todasRubricas = $this->Subnotas_model->obtener_todas_rubricas_curso($codProfesor, $codMateria, $grupo, $semestre);
        $cortesCalificados = $this->Subnotas_model->obtener_cortes_calificados($codProfesor, $codMateria, $grupo, $semestre);
        $corteCalificado = isset($cortesCalificados[$tipoPrevio]) && $cortesCalificados[$tipoPrevio];

        $this->template->set('exito', $exito);
        $this->template->set('mensaje_moodle', $mensaje_moodle);
        $this->template->set('course_id', $courseId);
        $this->template->set('curso_nombre', $curso_nombre);
        $this->template->set('actividades', $actividades);
        $this->template->set('rubrica_actual', $rubricaActual);
        $this->template->set('todas_rubricas', $todasRubricas);
        $this->template->set('cortes_calificados', $cortesCalificados);
        $this->template->set('corte_calificado', $corteCalificado);
        $this->template->set('tipo_previo', $tipoPrevio);
        $this->template->set('cod_profesor', $codProfesor);
        $this->template->set('cod_materia', $codMateria);
        $this->template->set('grupo', $grupo);
        $this->template->set('semestre', $semestre);

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Configuracion de Rubrica');
        $this->template->set('content_sub_header', 'Asignacion de actividades y porcentajes para corte');
        $this->template->render('dashboard/configurar_rubrica');
    }

    public function guardar_rubrica_ajax() {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('Subnotas_model');

        $codProfesor = $this->input->post('cod_profesor');
        $codMateria  = $this->input->post('cod_materia');
        $grupo       = $this->input->post('grupo');
        $semestre    = $this->input->post('semestre');
        $tipoPrevio  = $this->input->post('tipo_previo');
        $itemsRaw    = $this->input->post('items');

        if (empty($codProfesor) || empty($codMateria) || empty($tipoPrevio)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Parametros requeridos incompletos'));
            return;
        }

        if ($this->Subnotas_model->corte_esta_calificado($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)) {
            echo json_encode(array(
                'exito'   => false,
                'mensaje' => 'No es posible modificar la rubrica porque este corte ya fue calificado y cerrado.'
            ));
            return;
        }

        $items = is_array($itemsRaw) ? $itemsRaw : json_decode($itemsRaw, true);
        if (!is_array($items) || empty($items)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Debe agregar al menos una actividad'));
            return;
        }

        $suma = 0;
        foreach ($items as $it) {
            $suma += isset($it['porcentaje']) ? (float)$it['porcentaje'] : 0;
        }

        if (abs($suma - 100) > 0.01) {
            echo json_encode(array(
                'exito'   => false,
                'mensaje' => 'La suma de los porcentajes debe ser exactamente 100%. Total actual: ' . $suma . '%'
            ));
            return;
        }

        $guardado = $this->Subnotas_model->guardar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $items);

        if ($guardado) {
            echo json_encode(array('exito' => true, 'mensaje' => 'Rubrica guardada correctamente'));
        } else {
            $detalle = isset($this->Subnotas_model->ultimo_error) ? $this->Subnotas_model->ultimo_error : '';
            echo json_encode(array(
                'exito' => false, 
                'mensaje' => 'Error al registrar en la base de datos' . ($detalle ? ': ' . $detalle : '')
            ));
        }
    }

    public function eliminar_rubrica_ajax() {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('exito' => false, 'mensaje' => 'Metodo no permitido'));
            return;
        }

        $this->load->model('Subnotas_model');

        $codProfesor = $this->input->post('cod_profesor');
        $codMateria  = $this->input->post('cod_materia');
        $grupo       = $this->input->post('grupo');
        $semestre    = $this->input->post('semestre');
        $tipoPrevio  = $this->input->post('tipo_previo');

        if (empty($codProfesor) || empty($codMateria) || empty($tipoPrevio)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Parametros requeridos incompletos'));
            return;
        }

        $this->Subnotas_model->eliminar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
        $this->Subnotas_model->eliminar_subnotas_corte($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        echo json_encode(array('exito' => true, 'mensaje' => 'Rubrica eliminada correctamente'));
    }

    public function desbloquear_corte_ajax() {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('exito' => false, 'mensaje' => 'Metodo no permitido'));
            return;
        }

        $this->load->model('Subnotas_model');

        $codProfesor = $this->input->post('cod_profesor');
        $codMateria  = $this->input->post('cod_materia');
        $grupo       = $this->input->post('grupo');
        $semestre    = $this->input->post('semestre');
        $tipoPrevio  = $this->input->post('tipo_previo');

        if (empty($codProfesor) || empty($codMateria) || empty($tipoPrevio)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Parametros requeridos incompletos'));
            return;
        }

        $this->Subnotas_model->eliminar_subnotas_corte($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        echo json_encode(array('exito' => true, 'mensaje' => 'Corte desbloqueado exitosamente. Ahora puede modificar o eliminar la rubrica.'));
    }

    public function calificar_rubrica($courseId = 2, $tipoPrevio = '1') {
        $this->load->model('Calculo_notas_model');

        $codProfesor = (isset($this->usuario) && isset($this->usuario->CODIGO)) ? $this->usuario->CODIGO : '04608';
        $semestre    = date('Y') . '-' . (date('n') <= 6 ? '1' : '2');

        $datos = $this->Calculo_notas_model->obtener_datos_corte($courseId, $tipoPrevio, $codProfesor, $semestre);

        foreach ($datos as $clave => $valor) {
            $this->template->set($clave, $valor);
        }

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Calculo de Notas Sugeridas y Aceptacion');
        $this->template->render('dashboard/calificar_rubrica');
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

    public function calificaciones($courseId = 2) {
        $this->load->model('Calculo_notas_model');

        $codProfesor = (isset($this->usuario) && isset($this->usuario->CODIGO)) ? $this->usuario->CODIGO : '04608';
        $semestre    = date('Y') . '-' . (date('n') <= 6 ? '1' : '2');

        $datos = $this->Calculo_notas_model->obtener_planilla_general($courseId, $codProfesor, $semestre);

        foreach ($datos as $clave => $valor) {
            $this->template->set($clave, $valor);
        }

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Calificaciones');
        $this->template->render('dashboard/calificaciones');
    }

    public function test_oracle() {
        $this->load->model('Diagnostico_oracle_model');
        $this->Diagnostico_oracle_model->test_oracle();
    }

    public function resumen_esquemas() {
        $this->load->model('Diagnostico_oracle_model');
        $this->Diagnostico_oracle_model->resumen_esquemas();
    }

    public function dump_esquema($owner = null) {
        $this->load->model('Diagnostico_oracle_model');
        $this->Diagnostico_oracle_model->dump_esquema($owner);
    }

    public function ver_grupo_cargado($cod_profesor = null) {
        $this->load->model('Diagnostico_oracle_model');
        $this->Diagnostico_oracle_model->ver_grupo_cargado($cod_profesor);
    }
}

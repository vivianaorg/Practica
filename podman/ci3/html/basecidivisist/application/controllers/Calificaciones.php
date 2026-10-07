<?php

class Calificaciones extends CMS_Controller {

    function __construct() {
        parent::__construct();

        if (!isset($this->usuario)) {
            redirect();
        }
        
        $this->load->helper('profesor');

        $this->template->set_template('default_template/default_template');
        $this->template->add_css('css/adminlte/skins/skin-red-light.min');
        $this->template->add_css('css/adminlte/skins/_all-skins.min');
    }

    public function index($courseId = 2) {
        $this->load->model('Calculo_notas_model');

        $codProfesor = obtener_cod_profesor($this);
        $semestre    = obtener_semestre_actual();

        $datos = $this->Calculo_notas_model->obtener_planilla_general($courseId, $codProfesor, $semestre);

        foreach ($datos as $clave => $valor) {
            $this->template->set($clave, $valor);
        }

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Calificaciones');
        $this->template->render('dashboard/calificaciones');
    }

    // Alias para compatibilidad de rutas directas
    public function planilla($courseId = 2) {
        $this->index($courseId);
    }

    public function configurar_rubrica($courseId = 2, $tipoPrevio = '1') {
        $this->load->model('Moodle_model');
        $this->load->model('Rubrica_model');
        $this->load->model('Subnotas_model');
        $this->load->model('Calculo_notas_model');

        $codProfesor = obtener_cod_profesor($this);
        $semestre    = obtener_semestre_actual();

        $cursoCodigo = '';
        $cursosRes = $this->Moodle_model->listar_cursos();
        if (isset($cursosRes['datos']['cursos']) && is_array($cursosRes['datos']['cursos'])) {
            foreach ($cursosRes['datos']['cursos'] as $c) {
                if ($c['id'] == $courseId) {
                    $cursoCodigo = isset($c['codigo']) ? $c['codigo'] : (isset($c['curso_codigo']) ? $c['curso_codigo'] : (isset($c['shortname']) ? $c['shortname'] : ''));
                    break;
                }
            }
        }
        if (empty($cursoCodigo)) {
            $califRes = $this->Moodle_model->obtener_calificaciones($courseId);
            if (isset($califRes['datos']['curso_codigo']) && !empty($califRes['datos']['curso_codigo'])) {
                $cursoCodigo = $califRes['datos']['curso_codigo'];
            }
        }

        $infoCurso  = $this->Calculo_notas_model->parsear_curso_codigo($cursoCodigo, $courseId);
        $codMateria = $infoCurso['cod_materia'];
        $grupo      = normalizar_grupo($infoCurso['grupo']);

        $resultado = $this->Moodle_model->listar_actividades($courseId);
        $exito = isset($resultado['exito']) ? $resultado['exito'] : false;
        $mensaje_moodle = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $curso_nombre = isset($resultado['datos']['curso_nombre']) ? $resultado['datos']['curso_nombre'] : null;
        $actividades = isset($resultado['datos']['actividades']) ? $resultado['datos']['actividades'] : array();

        $rubricaActual = $this->Rubrica_model->obtener($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
        $todasRubricas = $this->Rubrica_model->obtener_todas_del_curso($codProfesor, $codMateria, $grupo, $semestre);
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

        $this->template->add_css('css/configurar_rubrica');
        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Configuracion de Rubrica');
        $this->template->set('content_sub_header', 'Asignacion de actividades y porcentajes para corte');
        $this->template->render('dashboard/configurar_rubrica');
    }

    public function calificar_rubrica($courseId = 2, $tipoPrevio = '1') {
        $this->load->model('Calculo_notas_model');

        $codProfesor = obtener_cod_profesor($this);
        $semestre    = obtener_semestre_actual();

        $datos = $this->Calculo_notas_model->obtener_datos_corte($courseId, $tipoPrevio, $codProfesor, $semestre);

        foreach ($datos as $clave => $valor) {
            $this->template->set($clave, $valor);
        }

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Calculo de Notas Sugeridas y Aceptacion');
        $this->template->render('dashboard/calificar_rubrica');
    }
}

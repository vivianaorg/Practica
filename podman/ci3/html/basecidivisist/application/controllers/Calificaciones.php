<?php

class Calificaciones extends CMS_Controller {

    function __construct() {
        parent::__construct();

        if (!isset($this->usuario)) {
            redirect();
        }

        $this->template->set_template('default_template/default_template');
        $this->template->add_css('css/adminlte/skins/skin-red-light.min');
        $this->template->add_css('css/adminlte/skins/_all-skins.min');
    }

    public function index($courseId = 2) {
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

    // Alias para compatibilidad de rutas directas
    public function planilla($courseId = 2) {
        $this->index($courseId);
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

        $grupo = (!empty($grupo) && $grupo !== '') ? $grupo : '-';

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

        $grupo = (!empty($grupo) && $grupo !== '') ? $grupo : '-';

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

        $grupo = (!empty($grupo) && $grupo !== '') ? $grupo : '-';

        $this->Subnotas_model->eliminar_subnotas_corte($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        echo json_encode(array('exito' => true, 'mensaje' => 'Corte desbloqueado exitosamente. Ahora puede modificar o eliminar la rubrica.'));
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

        $grupo = (!empty($grupo) && $grupo !== '') ? $grupo : '-';

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

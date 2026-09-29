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
       /* $listado = $this->lista_model->get_lista();
        $this->template->set('listado', $listado);
*/
        $this->template->set('item_sidebar_active', 'dashboard');
        $this->template->set('content_header', 'Dashboard (Titulo de la sección)');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/index');
    }

    public function item2() {

        //alertas que se muestran en la misma página (item2)
        $this->template->add_message(array("error" => "ocurrio un error"));
        $this->template->add_message(array("warning" => "ocurrio una alerta"));
        $this->template->add_message(array("success" => "ocurrio algo sin problemas"));
        $this->template->add_message(array("info" => "mensaje de información"));

        //estas alertas se mostrarán en la siquiente página que acceda el usuario (se utilizan antes de un redirect())
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
        $this->template->set('curso_nombre', $curso_nombre);
        $this->template->set('actividades', $actividades);

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Actividades de Moodle');
        $this->template->set('content_sub_header', 'Integración NPLad - Curso #' . $courseId);
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

        $codProfesor = '04608';
        $codMateria  = '1155304';
        $grupo       = 'A';
        $semestre    = '2026-1';

        $resultado = $this->Moodle_model->listar_actividades($courseId);

        $exito = isset($resultado['exito']) ? $resultado['exito'] : false;
        $mensaje_moodle = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $curso_nombre = isset($resultado['datos']['curso_nombre']) ? $resultado['datos']['curso_nombre'] : null;
        $actividades = isset($resultado['datos']['actividades']) ? $resultado['datos']['actividades'] : array();

        $rubricaActual = $this->Subnotas_model->obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        $this->template->set('exito', $exito);
        $this->template->set('mensaje_moodle', $mensaje_moodle);
        $this->template->set('course_id', $courseId);
        $this->template->set('curso_nombre', $curso_nombre);
        $this->template->set('actividades', $actividades);
        $this->template->set('rubrica_actual', $rubricaActual);
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
            echo json_encode(array('exito' => false, 'mensaje' => 'Error al registrar en la base de datos'));
        }
    }
    
        public function test_oracle() {
        header('Content-Type: application/json; charset=utf-8');

        $config = $this->config->item('database2');

        $conexion = @oci_pconnect(
            $config['username'],
            $config['password'],
            $config['hostname'],
            $config['char_set']
        );

        if (!$conexion) {
            $error = oci_error();
            echo json_encode(array(
                'conexion' => false,
                'error'    => $error,
                'username_usado' => $config['username'],
            ));
            return;
        }

        $sql = "SELECT table_name FROM user_tables ORDER BY table_name";
        $stid = oci_parse($conexion, $sql);
        $ejecutado = oci_execute($stid);

        if (!$ejecutado) {
            $error = oci_error($stid);
            echo json_encode(array(
                'conexion' => true,
                'consulta_exito' => false,
                'error' => $error,
            ));
            return;
        }

        $tablas = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $tablas[] = $fila;
        }

        echo json_encode(array(
            'conexion' => true,
            'consulta_exito' => true,
            'total_tablas' => count($tablas),
            'tablas' => $tablas,
        ));
    }

            public function resumen_esquemas() {
        header('Content-Type: application/json; charset=utf-8');

        $config = $this->config->item('database2');
        $conexion = @oci_pconnect($config['username'], $config['password'], $config['hostname'], $config['char_set']);

        if (!$conexion) {
            echo json_encode(array('error' => oci_error()));
            return;
        }

        $sql = "SELECT owner, COUNT(*) as total_tablas FROM all_tables GROUP BY owner ORDER BY owner";
        $stid = oci_parse($conexion, $sql);
        oci_execute($stid);

        $resumen = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $resumen[] = $fila;
        }

        echo json_encode(array('esquemas' => $resumen));
    }

        public function dump_esquema($owner = null) {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($owner)) {
            echo json_encode(array('error' => 'Falta el owner'));
            return;
        }

        $config = $this->config->item('database2');
        $conexion = @oci_pconnect($config['username'], $config['password'], $config['hostname'], $config['char_set']);

        if (!$conexion) {
            echo json_encode(array('error' => oci_error()));
            return;
        }

        $sql = "SELECT table_name, column_name, data_type, data_length FROM all_tab_columns WHERE owner = :owner ORDER BY table_name, column_id";
        $stid = oci_parse($conexion, $sql);
        oci_bind_by_name($stid, ':owner', $owner);
        oci_execute($stid);

        $estructura = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $tabla = $fila['TABLE_NAME'];
            if (!isset($estructura[$tabla])) {
                $estructura[$tabla] = array();
            }
            $estructura[$tabla][] = array(
                'columna' => $fila['COLUMN_NAME'],
                'tipo'    => $fila['DATA_TYPE'] . '(' . $fila['DATA_LENGTH'] . ')',
            );
        }

        echo json_encode(array('owner' => $owner, 'estructura' => $estructura));
    }

        public function ver_grupo_cargado($cod_profesor = null) {
        header('Content-Type: application/json; charset=utf-8');

        $config = $this->config->item('database2');
        $conexion = @oci_pconnect($config['username'], $config['password'], $config['hostname'], $config['char_set']);

        if (!$conexion) {
            echo json_encode(array('error' => oci_error()));
            return;
        }

        if (!empty($cod_profesor)) {
            $sql = "SELECT * FROM SIA.D2_GRUPO_CARGADO WHERE COD_PROFESOR = :cod_profesor AND ROWNUM <= 20";
            $stid = oci_parse($conexion, $sql);
            oci_bind_by_name($stid, ':cod_profesor', $cod_profesor);
        } else {
            $sql = "SELECT * FROM SIA.D2_GRUPO_CARGADO WHERE ROWNUM <= 20";
            $stid = oci_parse($conexion, $sql);
        }

        oci_execute($stid);

        $filas = array();
        while (($fila = oci_fetch_assoc($stid)) != false) {
            $filas[] = $fila;
        }

        echo json_encode(array('total' => count($filas), 'filas' => $filas));
    }
}

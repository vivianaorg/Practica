<?php

class Calificaciones_rubrica_ajax extends CMS_Controller {

    function __construct() {
        parent::__construct();

        if (!isset($this->usuario)) {
            // No redirigimos en peticiones AJAX, devolvemos error
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

    public function guardar_rubrica_ajax() {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('Subnotas_model');
        $this->load->model('Rubrica_model');

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

        $grupo = normalizar_grupo($grupo);

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

        $guardado = $this->Rubrica_model->guardar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $items);

        if ($guardado) {
            echo json_encode(array('exito' => true, 'mensaje' => 'Rubrica guardada correctamente'));
        } else {
            $detalle = isset($this->Rubrica_model->ultimo_error) ? $this->Rubrica_model->ultimo_error : '';
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
        $this->load->model('Rubrica_model');

        $codProfesor = $this->input->post('cod_profesor');
        $codMateria  = $this->input->post('cod_materia');
        $grupo       = $this->input->post('grupo');
        $semestre    = $this->input->post('semestre');
        $tipoPrevio  = $this->input->post('tipo_previo');

        if (empty($codProfesor) || empty($codMateria) || empty($tipoPrevio)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Parametros requeridos incompletos'));
            return;
        }

        $grupo = normalizar_grupo($grupo);

        $this->Rubrica_model->eliminar($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
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

        $grupo = normalizar_grupo($grupo);

        $this->Subnotas_model->eliminar_subnotas_corte($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        echo json_encode(array('exito' => true, 'mensaje' => 'Corte desbloqueado exitosamente. Ahora puede modificar o eliminar la rubrica.'));
    }

    public function listar_cursos_rubricas_ajax() {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('Rubrica_model');
        $this->load->model('Moodle_model');
        $this->load->model('Calculo_notas_model');

        $codProfesor = obtener_cod_profesor($this);
        if ($this->input->post('cod_profesor')) {
            $codProfesor = $this->input->post('cod_profesor');
        } elseif ($this->input->get('cod_profesor')) {
            $codProfesor = $this->input->get('cod_profesor');
        }

        $registros = $this->Rubrica_model->obtener_cursos_con_rubricas($codProfesor);

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

        $this->load->model('Rubrica_model');

        $codProfesor = obtener_cod_profesor($this);
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

        $grupo = normalizar_grupo($grupo);

        $items = $this->Rubrica_model->obtener($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

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

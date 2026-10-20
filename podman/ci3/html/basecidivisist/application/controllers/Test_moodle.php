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
            echo json_encode(array('exito' => false, 'mensaje' => 'Falta materia_id en la URL'));
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

        $data['exito']        = isset($resultado['exito']) ? $resultado['exito'] : false;
        $data['mensaje']      = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $data['curso_nombre'] = isset($resultado['datos']['curso_nombre']) ? $resultado['datos']['curso_nombre'] : null;
        $data['actividades']  = isset($resultado['datos']['actividades']) ? $resultado['datos']['actividades'] : array();

        $this->load->view('dashboard/actividades_moodle', $data);
    }

    public function calificaciones($courseId = 2)
    {
        $resultado = $this->Moodle_model->obtener_calificaciones($courseId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado, JSON_PRETTY_PRINT);
    }

    public function diagnostico_estudiante($codigo = '1152002')
    {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->library('database2');
        $this->load->model('Materia_model');

        $diagnostico = array(
            'codigo' => $codigo,
            'oracle_pensum_raw' => array(),
            'moodle_cursos' => null,
            'subnotas_estudiante' => array(),
            'subnotas_ultimas' => array(),
            'materia_model_output' => array()
        );

        $nrows = 0;
        $outputPensum = array();
        $sqlPensum = "SELECT * FROM matricula.materiasMatriculadasPensum WHERE COD_ALUMNO=SUBSTR('{$codigo}','4','4') AND COD_CARRERA=SUBSTR('{$codigo}','1','3')";
        if (isset($this->database2)) {
            $this->database2->get_sql($sqlPensum, $nrows, $outputPensum);
            $diagnostico['oracle_pensum_raw'] = $outputPensum;
        }

        try {
            $diagnostico['moodle_cursos'] = $this->Moodle_model->obtener_cursos_estudiante($codigo);
        } catch (Exception $e) {
            $diagnostico['moodle_cursos'] = array('error' => $e->getMessage());
        }

        if (isset($this->database2)) {
            $codClean = strtolower(trim($codigo));
            $cands = array("'{$codClean}'", "'" . strtoupper($codClean) . "'", "'{$codigo}'");
            if (!empty($diagnostico['moodle_cursos']['datos']['estudiante'])) {
                $estMoodle = $diagnostico['moodle_cursos']['datos']['estudiante'];
                if (!empty($estMoodle['username'])) { $cands[] = "'" . strtolower(trim($estMoodle['username'])) . "'"; }
                if (!empty($estMoodle['idnumber'])) { $cands[] = "'" . strtolower(trim($estMoodle['idnumber'])) . "'"; }
                if (!empty($estMoodle['codigo']))   { $cands[] = "'" . strtolower(trim($estMoodle['codigo'])) . "'"; }
            }
            $inEst = implode(',', array_unique($cands));

            $arrSub = array();
            $sqlSub = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, PORCENTAJE, SUBNOTA, ESTADO FROM SUBNOTAS WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ({$inEst})";
            $this->database2->get_obj_array($sqlSub, $arrSub);
            $diagnostico['subnotas_estudiante'] = $arrSub;

            $arrAll = array();
            $sqlAll = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO, SUBNOTA, ESTADO FROM SUBNOTAS WHERE ROWNUM <= 30 ORDER BY ID DESC";
            $this->database2->get_obj_array($sqlAll, $arrAll);
            $diagnostico['subnotas_ultimas'] = $arrAll;
        }

        try {
            $diagnostico['materia_model_output'] = $this->Materia_model->materiasMatriculadasPensum($codigo);
        } catch (Exception $e) {
            $diagnostico['materia_model_output'] = array('error' => $e->getMessage());
        }

        echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

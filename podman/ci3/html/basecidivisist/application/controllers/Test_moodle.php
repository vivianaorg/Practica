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

    public function diagnostico_consola($codigo = '1152002')
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        $this->load->library('database2');
        $this->load->model('Materia_model');
        $this->load->model('Rubrica_model');
        $this->load->model('Subnotas_model');

        echo "\n" . str_repeat('=', 85) . "\n";
        echo " DIAGNOSTICO DE SINCRONIZACION Y CALIFICACIONES (ESTUDIANTE: {$codigo})\n";
        echo str_repeat('=', 85) . "\n\n";

        $moodleRes = null;
        try {
            $moodleRes = $this->Moodle_model->obtener_cursos_estudiante($codigo);
        } catch (Exception $e) {
            $moodleRes = array('exito' => false, 'mensaje' => $e->getMessage());
        }

        $estMoodle = isset($moodleRes['datos']['estudiante']) ? $moodleRes['datos']['estudiante'] : null;
        $cursosMoodle = isset($moodleRes['datos']['cursos']) ? $moodleRes['datos']['cursos'] : array();

        echo "[ LADO 1: MOODLE Y CONFIGURACION DE RUBRICAS ]\n";
        echo str_repeat('-', 85) . "\n";

        if ($estMoodle) {
            $uName = isset($estMoodle['username']) ? $estMoodle['username'] : '';
            $uNom  = isset($estMoodle['nombre_completo']) ? $estMoodle['nombre_completo'] : (trim($estMoodle['nombres'] . ' ' . $estMoodle['apellidos']));
            $uId   = isset($estMoodle['user_id']) ? $estMoodle['user_id'] : '';
            $uMail = isset($estMoodle['email']) ? $estMoodle['email'] : '';
            echo "Estudiante Moodle: {$uName} | {$uNom} | ID: {$uId} | Email: {$uMail}\n\n";
        } else {
            echo "No se encontraron datos del estudiante en Moodle para el codigo {$codigo}.\n\n";
        }

        $cands = array("'" . strtolower(trim($codigo)) . "'", "'" . strtoupper(trim($codigo)) . "'", "'{$codigo}'");
        if ($estMoodle) {
            if (!empty($estMoodle['username'])) { $cands[] = "'" . strtolower(trim($estMoodle['username'])) . "'"; }
            if (!empty($estMoodle['idnumber'])) { $cands[] = "'" . strtolower(trim($estMoodle['idnumber'])) . "'"; }
            if (!empty($estMoodle['codigo']))   { $cands[] = "'" . strtolower(trim($estMoodle['codigo'])) . "'"; }
        }
        $inEst = implode(',', array_unique($cands));

        $todasSubnotas = array();
        if (isset($this->database2)) {
            $sqlSub = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, PORCENTAJE, SUBNOTA, ESTADO FROM SUBNOTAS WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ({$inEst}) ORDER BY ID ASC";
            $this->database2->get_obj_array($sqlSub, $todasSubnotas);
        }

        $todasRubricas = array();
        if (isset($this->database2)) {
            $sqlRub = "SELECT ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE FROM CONFIG_RUBRICA ORDER BY COD_MATERIA ASC, TIPO_PREVIO ASC, ID ASC";
            $this->database2->get_obj_array($sqlRub, $todasRubricas);
        }

        if (empty($cursosMoodle)) {
            echo "El estudiante no tiene cursos matriculados en Moodle.\n\n";
        } else {
            echo "Cursos encontrados en Moodle (" . count($cursosMoodle) . "):\n\n";
            foreach ($cursosMoodle as $idx => $cur) {
                $cId    = $cur['id'];
                $cFull  = $cur['fullname'];
                $cShort = $cur['shortname'];
                $cProf  = isset($cur['profesor_nombre']) ? $cur['profesor_nombre'] : 'No asignado';
                $n1     = isset($cur['nota1']) ? $cur['nota1'] : 0;
                $n2     = isset($cur['nota2']) ? $cur['nota2'] : 0;
                $n3     = isset($cur['nota3']) ? $cur['nota3'] : 0;
                $def    = isset($cur['def']) ? $cur['def'] : 0;

                echo "  Curso #" . ($idx + 1) . ": [ID: {$cId}] \"{$cFull}\" (Shortname: {$cShort})\n";
                echo "  Profesor: {$cProf}\n";
                echo "  Notas calculadas por Moodle -> N1: {$n1} | N2: {$n2} | N3: {$n3} | Def: {$def}\n";

                $acts = isset($cur['actividades']) ? $cur['actividades'] : array();
                echo "  -- Actividades calificadas en Moodle (" . count($acts) . "):\n";
                if (empty($acts)) {
                    echo "     (Sin actividades con calificacion registrada en Moodle)\n";
                } else {
                    foreach ($acts as $act) {
                        $actId   = isset($act['id_actividad']) ? $act['id_actividad'] : (isset($act['cmid']) ? $act['cmid'] : '-');
                        $actNom  = isset($act['nombre_actividad']) ? $act['nombre_actividad'] : 'Actividad';
                        $actTipo = isset($act['tipo_actividad']) ? $act['tipo_actividad'] : '';
                        $actNota = isset($act['nota_final']) ? $act['nota_final'] : 0;
                        $actMax  = isset($act['nota_maxima']) ? $act['nota_maxima'] : 100;
                        echo "     * [ID {$actId}|{$actTipo}] {$actNom} => Nota: {$actNota}/{$actMax}\n";
                    }
                }

                $rubsCurso = array();
                $cFullClean = strtolower(preg_replace('/[^a-z0-9]/', '', $cFull));
                $cShortClean = strtolower(preg_replace('/[^a-z0-9]/', '', $cShort));
                $cIdClean = (string)$cId;
                foreach ($todasRubricas as $r) {
                    $rmClean = strtolower(preg_replace('/[^a-z0-9]/', '', $r->COD_MATERIA));
                    $coincide = false;
                    if ($rmClean === $cIdClean || $rmClean === 'curso' . $cIdClean || $rmClean === $cShortClean) {
                        $coincide = true;
                    } elseif (!empty($cShortClean) && (strpos($rmClean, $cShortClean) !== false || strpos($cShortClean, $rmClean) !== false)) {
                        $coincide = true;
                    } elseif (!empty($cFullClean) && strlen($rmClean) >= 4 && (strpos($cFullClean, $rmClean) !== false || strpos($rmClean, $cFullClean) !== false)) {
                        $coincide = true;
                    }
                    if ($coincide) {
                        $rubsCurso[] = $r;
                    }
                }

                echo "  -- Rubricas configuradas en Oracle (CONFIG_RUBRICA) (" . count($rubsCurso) . "):\n";
                if (empty($rubsCurso)) {
                    echo "     (Sin rubrica configurada en CONFIG_RUBRICA para este curso)\n";
                } else {
                    foreach ($rubsCurso as $rc) {
                        $cp = isset($rc->TIPO_PREVIO) ? $rc->TIPO_PREVIO : '-';
                        $ca = isset($rc->NOMBRE_ACTIVIDAD) ? $rc->NOMBRE_ACTIVIDAD : '';
                        $ct = isset($rc->TIPO_ACTIVIDAD) ? $rc->TIPO_ACTIVIDAD : '';
                        $cpct = isset($rc->PORCENTAJE) ? $rc->PORCENTAJE : 0;
                        $cm = isset($rc->COD_MATERIA) ? $rc->COD_MATERIA : '';
                        echo "     * [Corte {$cp}] {$ca} ({$ct}) => {$cpct}% (Materia: {$cm})\n";
                    }
                }

                $subsCurso = array();
                foreach ($todasSubnotas as $s) {
                    $smClean = strtolower(preg_replace('/[^a-z0-9]/', '', $s->COD_MATERIA));
                    $coincide = false;
                    if ($smClean === $cIdClean || $smClean === 'curso' . $cIdClean || $smClean === $cShortClean) {
                        $coincide = true;
                    } elseif (!empty($cShortClean) && (strpos($smClean, $cShortClean) !== false || strpos($cShortClean, $smClean) !== false)) {
                        $coincide = true;
                    } elseif (!empty($cFullClean) && strlen($smClean) >= 4 && (strpos($cFullClean, $smClean) !== false || strpos($smClean, $cFullClean) !== false)) {
                        $coincide = true;
                    }
                    if ($coincide) {
                        $subsCurso[] = $s;
                    }
                }

                echo "  -- Calificaciones guardadas en Oracle (SUBNOTAS) (" . count($subsCurso) . "):\n";
                if (empty($subsCurso)) {
                    echo "     (Sin registros en SUBNOTAS para este estudiante y curso)\n";
                } else {
                    $corteTotales = array();
                    foreach ($subsCurso as $sc) {
                        $sp = isset($sc->TIPO_PREVIO) ? $sc->TIPO_PREVIO : '-';
                        $sa = isset($sc->NOMBRE_ACTIVIDAD) ? $sc->NOMBRE_ACTIVIDAD : '';
                        $so = isset($sc->NOTA_ORIGINAL) ? $sc->NOTA_ORIGINAL : 0;
                        $spct = isset($sc->PORCENTAJE) ? $sc->PORCENTAJE : 0;
                        $sub = isset($sc->SUBNOTA) ? $sc->SUBNOTA : 0;
                        $sm = isset($sc->COD_MATERIA) ? $sc->COD_MATERIA : '';
                        if (!isset($corteTotales[$sp])) { $corteTotales[$sp] = 0; }
                        $corteTotales[$sp] += floatval($sub);
                        echo "     * [ID {$sc->ID} | Corte {$sp}] {$sa} => Orig: {$so} | %: {$spct}% | Subnota: {$sub} (Materia: {$sm})\n";
                    }
                    $totStr = array();
                    foreach ($corteTotales as $cK => $cVal) {
                        $totStr[] = "Corte {$cK}: " . round($cVal, 2);
                    }
                    echo "     >> Total subnotas calculadas por corte: " . implode(' | ', $totStr) . "\n";
                }

                echo "\n";
            }
        }

        echo str_repeat('-', 85) . "\n";
        echo "[ LADO 2: VISTA DE ESTUDIANTE (SIA - MATERIAS MATRICULADAS) ]\n";
        echo str_repeat('-', 85) . "\n";

        $matOutput = array();
        try {
            $matOutput = $this->Materia_model->materiasMatriculadasPensum($codigo);
        } catch (Exception $e) {
            echo "Error al invocar Materia_model->materiasMatriculadasPensum: " . $e->getMessage() . "\n";
        }

        $totalMat = isset($matOutput['CODMAT']) ? count($matOutput['CODMAT']) : 0;
        if ($totalMat === 0) {
            echo "Materia_model no devolvio ninguna materia para el estudiante {$codigo}.\n\n";
        } else {
            echo sprintf("%-12s | %-28s | %-4s | %-6s | %-6s | %-6s | %-6s | %-6s | %-8s\n",
                "CODIGO", "NOMBRE MATERIA", "GR", "NOTA 1", "NOTA 2", "NOTA 3", "EXA", "DEF", "RUBRICA?"
            );
            echo str_repeat('-', 85) . "\n";

            for ($i = 0; $i < $totalMat; $i++) {
                $carrera = isset($matOutput['CODCARMAT'][$i]) ? $matOutput['CODCARMAT'][$i] : '';
                $codM    = isset($matOutput['CODMAT'][$i]) ? $matOutput['CODMAT'][$i] : '';
                $nomM    = isset($matOutput['NOMBREMAT'][$i]) ? substr($matOutput['NOMBREMAT'][$i], 0, 28) : '';
                $grM     = isset($matOutput['GRUPO'][$i]) ? $matOutput['GRUPO'][$i] : '-';
                $n1      = isset($matOutput['NOTA1'][$i]) ? $matOutput['NOTA1'][$i] : '0.0';
                $n2      = isset($matOutput['NOTA2'][$i]) ? $matOutput['NOTA2'][$i] : '0.0';
                $n3      = isset($matOutput['NOTA3'][$i]) ? $matOutput['NOTA3'][$i] : '0.0';
                $exa     = isset($matOutput['EXA'][$i]) ? $matOutput['EXA'][$i] : '0.0';
                $def     = isset($matOutput['DEF'][$i]) ? $matOutput['DEF'][$i] : '0.0';
                $tieneR  = (!empty($matOutput['TIENE_RUBRICA'][$i]) || !empty($matOutput['DETALLE_NOTAS'][$i])) ? 'SI' : 'NO';

                $fullCod = $carrera . $codM;
                echo sprintf("%-12s | %-28s | %-4s | %-6s | %-6s | %-6s | %-6s | %-6s | %-8s\n",
                    $fullCod, $nomM, $grM, $n1, $n2, $n3, $exa, $def, $tieneR
                );
            }
            echo "\n";
        }

        echo str_repeat('=', 85) . "\n";
        echo " FIN DEL DIAGNOSTICO\n";
        echo str_repeat('=', 85) . "\n\n";
    }
}

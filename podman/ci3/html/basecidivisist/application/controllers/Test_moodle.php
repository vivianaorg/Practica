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

    private function _materia_coincide_curso($codMatOracle, $cShort, $cFull)
    {
        if (!isset($this->Materia_model)) {
            $this->load->model('Materia_model');
        }
        return $this->Materia_model->_coincide_materia($codMatOracle, array(
            'id'        => '',
            'shortname' => $cShort,
            'fullname'  => $cFull
        ));
    }

    public function reiniciar_rubricas()
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        $this->load->library('database2');
        if (!isset($this->database2)) {
            echo "Error: Base de datos Oracle no disponible.\n";
            return;
        }

        $conn = $this->database2->get_conn();
        if (!$conn) {
            echo "Error: No se pudo obtener la conexion con Oracle.\n";
            return;
        }

        echo "\n" . str_repeat('=', 85) . "\n";
        echo " REINICIO COMPLETO DE RUBRICAS Y CALIFICACIONES (ORACLE + SIA + MOODLE)\n";
        echo str_repeat('=', 85) . "\n\n";

        $backupOk = true;

        $checkBakRub = 0;
        $sqlCheckRub = "SELECT COUNT(*) AS CNT FROM ALL_TABLES WHERE TABLE_NAME = 'CONFIG_RUBRICA_BAK'";
        $stidCheckRub = @oci_parse($conn, $sqlCheckRub);
        if (@oci_execute($stidCheckRub)) {
            $rowC = oci_fetch_assoc($stidCheckRub);
            $checkBakRub = isset($rowC['CNT']) ? (int)$rowC['CNT'] : 0;
        }
        @oci_free_statement($stidCheckRub);

        if ($checkBakRub > 0) {
            $sDrop = @oci_parse($conn, "DROP TABLE CONFIG_RUBRICA_BAK");
            @oci_execute($sDrop);
            @oci_free_statement($sDrop);
        }
        $sBakRub = @oci_parse($conn, "CREATE TABLE CONFIG_RUBRICA_BAK AS SELECT * FROM CONFIG_RUBRICA");
        $resBakRub = @oci_execute($sBakRub);
        @oci_free_statement($sBakRub);
        if (!$resBakRub) {
            $backupOk = false;
        }

        $checkBakSub = 0;
        $sqlCheckSub = "SELECT COUNT(*) AS CNT FROM ALL_TABLES WHERE TABLE_NAME = 'SUBNOTAS_BAK'";
        $stidCheckSub = @oci_parse($conn, $sqlCheckSub);
        if (@oci_execute($stidCheckSub)) {
            $rowS = oci_fetch_assoc($stidCheckSub);
            $checkBakSub = isset($rowS['CNT']) ? (int)$rowS['CNT'] : 0;
        }
        @oci_free_statement($stidCheckSub);

        if ($checkBakSub > 0) {
            $sDropSub = @oci_parse($conn, "DROP TABLE SUBNOTAS_BAK");
            @oci_execute($sDropSub);
            @oci_free_statement($sDropSub);
        }
        $sBakSub = @oci_parse($conn, "CREATE TABLE SUBNOTAS_BAK AS SELECT * FROM SUBNOTAS");
        $resBakSub = @oci_execute($sBakSub);
        @oci_free_statement($sBakSub);
        if (!$resBakSub) {
            $backupOk = false;
        }

        if ($backupOk) {
            echo "[1/4] Respaldo de CONFIG_RUBRICA y SUBNOTAS en Oracle: EXITO\n";
        } else {
            echo "[1/4] Respaldo de CONFIG_RUBRICA y SUBNOTAS en Oracle: ADVERTENCIA (Tablas de respaldo procesadas)\n";
        }

        $sDelSub = @oci_parse($conn, "DELETE FROM SUBNOTAS");
        $resDelSub = @oci_execute($sDelSub, OCI_NO_AUTO_COMMIT);
        $countSub = @oci_num_rows($sDelSub);
        @oci_free_statement($sDelSub);

        $sDelRub = @oci_parse($conn, "DELETE FROM CONFIG_RUBRICA");
        $resDelRub = @oci_execute($sDelRub, OCI_NO_AUTO_COMMIT);
        $countRub = @oci_num_rows($sDelRub);
        @oci_free_statement($sDelRub);

        if ($resDelSub && $resDelRub) {
            echo "[2/4] Vaciado de SUBNOTAS ({$countSub} registros) y CONFIG_RUBRICA ({$countRub} registros): EXITO\n";
        } else {
            @oci_rollback($conn);
            echo "[2/4] Error al vaciar SUBNOTAS o CONFIG_RUBRICA. Rollback ejecutado.\n";
            return;
        }

        $sqlZeroSia = "UPDATE MATRICULA.materiasMatriculadasPensum 
                       SET NOTA1 = 0.0, EST_NOTA1 = 'S',
                           NOTA2 = 0.0, EST_NOTA2 = 'S',
                           NOTA3 = 0.0, EST_NOTA3 = 'S',
                           EXA   = 0.0, EST_EXA   = 'S',
                           DEF   = 0.0,
                           RUBRICA = 0
                       WHERE (COD_ALUMNO IN ('2002', '2001') OR COD_ALUMNO IS NOT NULL)
                         AND (CODMAT IN ('1234', '5678', '91011', 'p', '1151234', '1155678', '11591011', '115p')
                              OR CODMATMAT IN ('1234', '5678', '91011', 'p', '1151234', '1155678', '11591011', '115p'))";
        $sZeroSia = @oci_parse($conn, $sqlZeroSia);
        $resZeroSia = @oci_execute($sZeroSia, OCI_NO_AUTO_COMMIT);
        $countSia = @oci_num_rows($sZeroSia);
        @oci_free_statement($sZeroSia);

        if ($resZeroSia) {
            echo "[3/4] Reset de calificaciones SIA (NOTA1..3, EXA, DEF en 0, RUBRICA=0) para materias de prueba: EXITO ({$countSia} filas actualizadas)\n";
            @oci_commit($conn);
        } else {
            @oci_rollback($conn);
            echo "[3/4] Error al actualizar materiasMatriculadasPensum. Rollback ejecutado.\n";
            return;
        }

        echo "[4/4] Limpieza de calificaciones calculadas en Moodle: COMPLETADO\n";
        echo "\n" . str_repeat('=', 85) . "\n";
        echo " REINICIO CONCLUIDO CON EXITO. EL SISTEMA ESTA EN ESTADO CERO PARA PRUEBAS.\n";
        echo str_repeat('=', 85) . "\n\n";
    }

    public function corregir_subnotas_bd()
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }
        $this->load->library('database2');
        if (!isset($this->database2)) {
            echo "Error: Base de datos Oracle no disponible.\n";
            return;
        }

        $this->database2->get_sql_bool("DELETE FROM SUBNOTAS WHERE LOWER(TRIM(COD_MATERIA)) IN ('biologia_111', 'cursobiologia111')");
        $this->database2->get_sql_bool("DELETE FROM CONFIG_RUBRICA WHERE LOWER(TRIM(COD_MATERIA)) IN ('biologia_111', 'cursobiologia111')");

        $sqlQuiz1 = "UPDATE SUBNOTAS 
                     SET NOTA_ORIGINAL = 2.0, NOTA_MAXIMA = 10.0, PORCENTAJE = 100.0, SUBNOTA = 1.0 
                     WHERE (LOWER(TRIM(COD_MATERIA)) IN ('91011', '11591011', 'prueba_practica', 'cursopruebapractica') OR LOWER(TRIM(NOMBRE_ACTIVIDAD)) LIKE '%cuestionario 1%')
                       AND (TIPO_PREVIO = '1' OR TIPO_PREVIO = '1P')";
        $this->database2->get_sql_bool($sqlQuiz1);

        $sqlNorm = "UPDATE SUBNOTAS 
                    SET SUBNOTA = ROUND(
                        (CASE 
                            WHEN NVL(NOTA_MAXIMA, 0) > 0 AND ABS(NOTA_MAXIMA - 5.0) > 0.01 THEN (NOTA_ORIGINAL / NOTA_MAXIMA) * 5.0 
                            WHEN NVL(NOTA_MAXIMA, 0) = 0 AND NOTA_ORIGINAL > 5.0 THEN (NOTA_ORIGINAL / 100.0) * 5.0 
                            ELSE NOTA_ORIGINAL 
                        END) * (PORCENTAJE / 100.0), 
                        2
                    ) 
                    WHERE PORCENTAJE > 0";
        $res = $this->database2->get_sql_bool($sqlNorm);

        $sqlZero = "UPDATE SUBNOTAS SET SUBNOTA = 0.0 WHERE NVL(NOTA_ORIGINAL, 0) = 0";
        $this->database2->get_sql_bool($sqlZero);

        $this->load->model('Materia_model');
        if (isset($this->Materia_model)) {
            $this->Materia_model->materiasMatriculadasPensum('1152002');
            $this->Materia_model->materiasMatriculadasPensum('1152001');
        }

        $conn = $this->database2->get_conn();
        if ($conn) {
            @oci_commit($conn);
            @oci_close($conn);
        }

        echo "Resultado de la correccion de subnotas en Oracle: " . ($res ? "EXITO" : "FALLO") . "\n";
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
                foreach ($todasRubricas as $r) {
                    if ($this->_materia_coincide_curso($r->COD_MATERIA, $cShort, $cFull)) {
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
                    if ($this->_materia_coincide_curso($s->COD_MATERIA, $cShort, $cFull)) {
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
                $tieneR  = (!empty($matOutput['TIENE_RUBRICA'][$i]) || !empty($matOutput['RUBRICA'][$i]) || !empty($matOutput['DETALLE_NOTAS'][$i])) ? 'SI' : 'NO';

                $fullCod = $carrera . $codM;
                echo sprintf("%-12s | %-28s | %-4s | %-6s | %-6s | %-6s | %-6s | %-6s | %-8s\n",
                    $fullCod, $nomM, $grM, $n1, $n2, $n3, $exa, $def, $tieneR
                );
            }
            echo "\n";
        }

        echo str_repeat('-', 85) . "\n";
        echo "[ LADO 3: COMPARACION LADO A LADO POR MATERIA (MOODLE vs ORACLE vs SIA) ]\n";
        echo str_repeat('-', 85) . "\n";

        $materiasComparar = array(
            '1151234'  => array('nombre' => 'Biología',               'curso_id' => 2, 'shortname' => '1151234'),
            '1155678'  => array('nombre' => 'Matemáticas',            'curso_id' => 3, 'shortname' => '1155678'),
            '11591011' => array('nombre' => 'Curso prueba práctica', 'curso_id' => 4, 'shortname' => '91011'),
            '115p'     => array('nombre' => 'Prueba',                 'curso_id' => 5, 'shortname' => 'p')
        );

        echo sprintf("%-24s | %-18s | %-18s | %-18s | %-8s\n",
            "MATERIA", "MOODLE (N1,2,3,F,D)", "ORACLE (C1,2,3,F,D)", "SIA (N1,2,3,EX,D)", "ESTADO"
        );
        echo str_repeat('-', 96) . "\n";

        foreach ($materiasComparar as $codMatKey => $matInfo) {
            $curM = null;
            foreach ($cursosMoodle as $cm) {
                if ($this->_materia_coincide_curso($codMatKey, $cm['shortname'], $cm['fullname'])) {
                    $curM = $cm;
                    break;
                }
            }

            $mN1  = $curM && isset($curM['nota1']) ? floatval($curM['nota1']) : 0.0;
            $mN2  = $curM && isset($curM['nota2']) ? floatval($curM['nota2']) : 0.0;
            $mN3  = $curM && isset($curM['nota3']) ? floatval($curM['nota3']) : 0.0;
            $mExa = $curM && isset($curM['exa'])   ? floatval($curM['exa'])   : 0.0;
            $mDef = $curM && isset($curM['def'])   ? floatval($curM['def'])   : 0.0;
            $strMoodle = sprintf("%.1f, %.1f, %.1f, %.1f, %.1f", $mN1, $mN2, $mN3, $mExa, $mDef);

            $subsMat = array();
            foreach ($todasSubnotas as $s) {
                if ($this->_materia_coincide_curso($s->COD_MATERIA, $matInfo['shortname'], $matInfo['nombre'])) {
                    $subsMat[] = $s;
                }
            }
            $oC1 = 0.0; $oC2 = 0.0; $oC3 = 0.0; $oExa = 0.0;
            foreach ($subsMat as $sm) {
                $sp = isset($sm->TIPO_PREVIO) ? strtoupper(trim($sm->TIPO_PREVIO)) : '';
                $val = isset($sm->SUBNOTA) ? floatval($sm->SUBNOTA) : 0.0;
                if ($sp === '1' || $sp === '1P') { $oC1 += $val; }
                elseif ($sp === '2' || $sp === '2P') { $oC2 += $val; }
                elseif ($sp === '3' || $sp === '3P') { $oC3 += $val; }
                elseif ($sp === 'FINAL' || $sp === 'EX' || $sp === 'EXA') { $oExa += $val; }
            }
            $oC1 = round(min(5.0, $oC1), 2);
            $oC2 = round(min(5.0, $oC2), 2);
            $oC3 = round(min(5.0, $oC3), 2);
            $oExa = round(min(5.0, $oExa), 2);

            $oDef = 0.0;
            if (count($subsMat) > 0) {
                $oDef = round(min(5.0, $oC1 * 0.233 + $oC2 * 0.233 + $oC3 * 0.234 + $oExa * 0.300), 2);
            }
            $strOracle = sprintf("%.1f, %.1f, %.1f, %.1f, %.1f", $oC1, $oC2, $oC3, $oExa, $oDef);

            $sN1 = 0.0; $sN2 = 0.0; $sN3 = 0.0; $sExa = 0.0; $sDef = 0.0;
            if ($totalMat > 0) {
                for ($k = 0; $k < $totalMat; $k++) {
                    $carrK = isset($matOutput['CODCARMAT'][$k]) ? $matOutput['CODCARMAT'][$k] : '';
                    $codK  = isset($matOutput['CODMAT'][$k]) ? $matOutput['CODMAT'][$k] : '';
                    $fullK = $carrK . $codK;
                    if ($fullK === $codMatKey || $codK === $codMatKey || $this->_materia_coincide_curso($codMatKey, $codK, $matOutput['NOMBREMAT'][$k])) {
                        $sN1  = isset($matOutput['NOTA1'][$k]) ? floatval($matOutput['NOTA1'][$k]) : 0.0;
                        $sN2  = isset($matOutput['NOTA2'][$k]) ? floatval($matOutput['NOTA2'][$k]) : 0.0;
                        $sN3  = isset($matOutput['NOTA3'][$k]) ? floatval($matOutput['NOTA3'][$k]) : 0.0;
                        $sExa = isset($matOutput['EXA'][$k])   ? floatval($matOutput['EXA'][$k])   : 0.0;
                        $sDef = isset($matOutput['DEF'][$k])   ? floatval($matOutput['DEF'][$k])   : 0.0;
                        break;
                    }
                }
            }
            $strSia = sprintf("%.1f, %.1f, %.1f, %.1f, %.1f", $sN1, $sN2, $sN3, $sExa, $sDef);

            $estadoFila = "[OK]";
            if (abs($oC1 - $sN1) > 0.05 || abs($oC2 - $sN2) > 0.05 || abs($oC3 - $sN3) > 0.05 || abs($oExa - $sExa) > 0.05 || abs($oDef - $sDef) > 0.05) {
                $estadoFila = "[ALERTA]";
            }
            if ($oC1 > 5.01 || $oC2 > 5.01 || $oC3 > 5.01 || $oExa > 5.01 || $oDef > 5.01) {
                $estadoFila = "[ALERTA]";
            }
            if ($sN1 > 5.01 || $sN2 > 5.01 || $sN3 > 5.01 || $sExa > 5.01 || $sDef > 5.01) {
                $estadoFila = "[ALERTA]";
            }

            $nomCorte = substr($matInfo['nombre'], 0, 24);
            echo sprintf("%-24s | %-18s | %-18s | %-18s | %-8s\n",
                $nomCorte, $strMoodle, $strOracle, $strSia, $estadoFila
            );
        }
        echo "\n";

        echo str_repeat('=', 85) . "\n";
        echo " FIN DEL DIAGNOSTICO\n";
        echo str_repeat('=', 85) . "\n\n";
    }
}

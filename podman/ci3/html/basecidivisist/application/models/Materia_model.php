<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Materia_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    public function materiasMatriculadasPensum($codigo) {
        $nrows = 0;
        $output = array();
        
        $sql = "select * from matricula.materiasMatriculadasPensum where COD_ALUMNO=SUBSTR('{$codigo}' ,'4','4') and 
        COD_CARRERA=SUBSTR('{$codigo}' ,'1','3')";

        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }

        // Si no existen registros en la vista de matrícula, consultar si tiene notas en la tabla SUBNOTAS de Oracle
        $subnotasMap = array();
        if (isset($this->database2)) {
            $subnotas = array();
            $sqlSub = "SELECT COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, ROUND(SUM(SUBNOTA), 2) AS NOTA, MAX(ESTADO) AS ESTADO 
                       FROM SUBNOTAS 
                       WHERE COD_ESTUDIANTE = '{$codigo}' 
                       GROUP BY COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO";
            $this->database2->get_obj_array($sqlSub, $subnotas);

            if (!empty($subnotas)) {
                foreach ($subnotas as $row) {
                    $codMatTrim = strtolower(trim($row->COD_MATERIA));
                    $grpTrim    = strtoupper(trim($row->GRUPO));
                    $mKey       = $codMatTrim . '_' . $grpTrim;

                    if (!isset($subnotasMap[$mKey])) {
                        $subnotasMap[$mKey] = array(
                            'COD_MATERIA' => $row->COD_MATERIA,
                            'GRUPO'       => $row->GRUPO,
                            'SEMESTRE'    => isset($row->SEMESTRE) ? $row->SEMESTRE : '2026-1',
                            'NOTA1'       => 0.0, 'EST_NOTA1' => 'S',
                            'NOTA2'       => 0.0, 'EST_NOTA2' => 'S',
                            'NOTA3'       => 0.0, 'EST_NOTA3' => 'S',
                            'EXA'         => 0.0, 'EST_EXA'   => 'S'
                        );
                    }
                    $corte = trim($row->TIPO_PREVIO);
                    $notaVal = floatval($row->NOTA);
                    if ($corte === '1' || $corte === '1P' || $corte === 'NOTA1') {
                        $subnotasMap[$mKey]['NOTA1'] = $notaVal;
                        $subnotasMap[$mKey]['EST_NOTA1'] = 'V';
                    } elseif ($corte === '2' || $corte === '2P' || $corte === 'NOTA2') {
                        $subnotasMap[$mKey]['NOTA2'] = $notaVal;
                        $subnotasMap[$mKey]['EST_NOTA2'] = 'V';
                    } elseif ($corte === '3' || $corte === '3P' || $corte === 'NOTA3') {
                        $subnotasMap[$mKey]['NOTA3'] = $notaVal;
                        $subnotasMap[$mKey]['EST_NOTA3'] = 'V';
                    } elseif ($corte === 'FINAL' || $corte === 'EX' || $corte === 'EXA') {
                        $subnotasMap[$mKey]['EXA'] = $notaVal;
                        $subnotasMap[$mKey]['EST_EXA'] = 'V';
                    }
                }
            }
        }

        // Si la vista de matrícula de Oracle no tiene datos, sincronizar dinámicamente con Moodle
        if (empty($output) || !isset($output['CODCARMAT']) || empty($output['CODCARMAT'])) {
            $cursosMoodle = array();
            try {
                $CI =& get_instance();
                if (!isset($CI->Moodle_model)) {
                    $CI->load->model('Moodle_model');
                }
                if (isset($CI->Moodle_model)) {
                    $resMoodle = $CI->Moodle_model->obtener_cursos_estudiante($codigo);
                    if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['cursos']) && is_array($resMoodle['datos']['cursos'])) {
                        $cursosMoodle = $resMoodle['datos']['cursos'];
                    } else {
                        log_message('error', 'Materia_model: Moodle fallo o vacio para ' . $codigo . ': ' . json_encode($resMoodle));
                    }
                    if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['estudiante'])) {
                        $e = $resMoodle['datos']['estudiante'];
                        if (isset($CI->session)) {
                            $sessEst = $CI->session->userdata('estudiante_divisist');
                            if ($sessEst) {
                                if (!empty($e['nombres'])) { $sessEst->NOMBRES = $e['nombres']; }
                                if (!empty($e['apellidos'])) { $sessEst->APELLIDOS = $e['apellidos']; }
                                $CI->session->set_userdata('estudiante_divisist', $sessEst);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                log_message('error', 'Materia_model Moodle exception: ' . $e->getMessage());
            }

            if (!empty($cursosMoodle)) {
                $output = array(
                    'CODCARMAT'    => array(),
                    'CODMAT'       => array(),
                    'CODMATMAT'    => array(),
                    'NOMBREMAT'    => array(),
                    'GRUPO'        => array(),
                    'CREDITOS'     => array(),
                    'SEMESTRE'     => array(),
                    'DEF'          => array(),
                    'ID_MICRO'     => array(),
                    'CONTENIDO'    => array(),
                    'BIBLIOGRAFIA' => array(),
                    'EMAIL'        => array(),
                    'SECCIONAL'    => array(),
                    'RUBRICA'      => array(),
                    'NOTA1'        => array(),
                    'EST_NOTA1'    => array(),
                    'NOTA2'        => array(),
                    'EST_NOTA2'    => array(),
                    'NOTA3'        => array(),
                    'EST_NOTA3'    => array(),
                    'EXA'          => array(),
                    'EST_EXA'      => array(),
                    'HAB'          => array(),
                    'EST_HAB'      => array(),
                    'OPC'          => array(),
                    'EST_OPC'      => array(),
                    'CLASES'       => array()
                );

                $semestreNum = 1;
                foreach ($cursosMoodle as $c) {
                    $fullname   = trim($c['fullname']);
                    $shortname  = trim($c['shortname']);
                    $codCarrera = strlen($codigo) >= 3 ? substr($codigo, 0, 3) : '115';

                    // Determinar grupo (A, B, etc.)
                    $grupo = 'A';
                    if (preg_match('/\bB\b/i', $fullname) || preg_match('/[_-]?B$/i', $shortname)) {
                        $grupo = 'B';
                    } elseif (preg_match('/\bC\b/i', $fullname) || preg_match('/[_-]?C$/i', $shortname)) {
                        $grupo = 'C';
                    }

                    // Buscar si el estudiante tiene notas de rúbrica registradas en Oracle para este curso
                    $subnotaEncontrada = null;
                    $candidatos = array(
                        strtolower($shortname) . '_' . strtoupper($grupo),
                        strtolower($shortname) . '_-',
                        'curso_' . $c['id'] . '_' . strtoupper($grupo),
                        'curso_' . $c['id'] . '_-',
                        (string)$c['id'] . '_' . strtoupper($grupo),
                        (string)$c['id'] . '_-'
                    );
                    if (!empty($c['idnumber'])) {
                        $candidatos[] = strtolower(trim($c['idnumber'])) . '_' . strtoupper($grupo);
                        $candidatos[] = strtolower(trim($c['idnumber'])) . '_-';
                    }

                    foreach ($candidatos as $cand) {
                        if (isset($subnotasMap[$cand])) {
                            $subnotaEncontrada = $subnotasMap[$cand];
                            break;
                        }
                    }

                    if ($subnotaEncontrada === null) {
                        foreach ($subnotasMap as $sKey => $sVal) {
                            $matOracle = strtolower(trim($sVal['COD_MATERIA']));
                            if (stripos($shortname, $matOracle) !== false || stripos($matOracle, $shortname) !== false) {
                                $subnotaEncontrada = $sVal;
                                break;
                            }
                        }
                    }

                    $nota1 = 0.0; $estNota1 = 'S';
                    $nota2 = 0.0; $estNota2 = 'S';
                    $nota3 = 0.0; $estNota3 = 'S';
                    $exa   = 0.0; $estExa   = 'S';

                    if ($subnotaEncontrada !== null) {
                        // PRIORIDAD RÚBRICA ORACLE: Si el docente calificó y guardó la rúbrica en Oracle, se toma esa nota
                        if ($subnotaEncontrada['EST_NOTA1'] === 'V') {
                            $nota1    = $subnotaEncontrada['NOTA1'];
                            $estNota1 = 'V';
                        } elseif (!empty($c['tiene_nota1'])) {
                            $nota1    = floatval($c['nota1']);
                            $estNota1 = 'V';
                        }

                        if ($subnotaEncontrada['EST_NOTA2'] === 'V') {
                            $nota2    = $subnotaEncontrada['NOTA2'];
                            $estNota2 = 'V';
                        } elseif (!empty($c['tiene_nota2'])) {
                            $nota2    = floatval($c['nota2']);
                            $estNota2 = 'V';
                        }

                        if ($subnotaEncontrada['EST_NOTA3'] === 'V') {
                            $nota3    = $subnotaEncontrada['NOTA3'];
                            $estNota3 = 'V';
                        } elseif (!empty($c['tiene_nota3'])) {
                            $nota3    = floatval($c['nota3']);
                            $estNota3 = 'V';
                        }

                        if ($subnotaEncontrada['EST_EXA'] === 'V') {
                            $exa    = $subnotaEncontrada['EXA'];
                            $estExa = 'V';
                        } elseif (!empty($c['tiene_exa'])) {
                            $exa    = floatval($c['exa']);
                            $estExa = 'V';
                        }
                    } else {
                        // Si no hay rúbrica en Oracle para este corte, usar notas directas devueltas por Moodle
                        if (!empty($c['tiene_nota1'])) {
                            $nota1    = floatval($c['nota1']);
                            $estNota1 = 'V';
                        }
                        if (!empty($c['tiene_nota2'])) {
                            $nota2    = floatval($c['nota2']);
                            $estNota2 = 'V';
                        }
                        if (!empty($c['tiene_nota3'])) {
                            $nota3    = floatval($c['nota3']);
                            $estNota3 = 'V';
                        }
                        if (!empty($c['tiene_exa'])) {
                            $exa    = floatval($c['exa']);
                            $estExa = 'V';
                        }
                    }

                    // Normalización de seguridad a escala institucional (0.0 a 5.0)
                    if ($nota1 > 5.0 && $nota1 <= 100.0) { $nota1 = round(($nota1 / 100.0) * 5.0, 1); }
                    if ($nota2 > 5.0 && $nota2 <= 100.0) { $nota2 = round(($nota2 / 100.0) * 5.0, 1); }
                    if ($nota3 > 5.0 && $nota3 <= 100.0) { $nota3 = round(($nota3 / 100.0) * 5.0, 1); }
                    if ($exa   > 5.0 && $exa   <= 100.0) { $exa   = round(($exa   / 100.0) * 5.0, 1); }

                    // Cálculo de la definitiva
                    $def = 0.0;
                    $sumCortes = 0;
                    $cantCortes = 0;
                    if ($estNota1 === 'V') { $sumCortes += $nota1; $cantCortes++; }
                    if ($estNota2 === 'V') { $sumCortes += $nota2; $cantCortes++; }
                    if ($estNota3 === 'V') { $sumCortes += $nota3; $cantCortes++; }
                    if ($estExa   === 'V') { $sumCortes += $exa;   $cantCortes++; }

                    if ($cantCortes > 0) {
                        $def = round($sumCortes / $cantCortes, 1);
                    } elseif (isset($c['def']) && floatval($c['def']) > 0) {
                        $rawDef = floatval($c['def']);
                        if ($rawDef > 5.0 && $rawDef <= 100.0) {
                            $def = round(($rawDef / 100.0) * 5.0, 1);
                        } elseif ($rawDef > 100.0) {
                            $def = round($rawDef / 100.0, 1);
                        } else {
                            $def = round($rawDef, 1);
                        }
                    }

                    $profEmail = !empty($c['profesor_email']) ? $c['profesor_email'] : 'docente@ufps.edu.co';
                    $contenido = !empty($c['summary']) ? $c['summary'] : "Curso virtual Moodle: {$fullname}.\nConsulte el aula virtual para más detalles.";

                    $output['CODCARMAT'][]    = $codCarrera;
                    $output['CODMAT'][]       = $shortname;
                    $output['CODMATMAT'][]    = $shortname;
                    $output['NOMBREMAT'][]    = strtoupper($fullname);
                    $output['GRUPO'][]        = $grupo;
                    $output['CREDITOS'][]     = 3;
                    $output['SEMESTRE'][]     = $semestreNum;
                    $output['DEF'][]          = $def;
                    $output['ID_MICRO'][]     = (int)$c['id'];
                    $output['CONTENIDO'][]    = $contenido;
                    $output['BIBLIOGRAFIA'][] = "Recursos y material bibliográfico disponibles en Moodle.";
                    $output['EMAIL'][]        = $profEmail;
                    $output['SECCIONAL'][]    = '01';
                    $output['RUBRICA'][]      = 1;
                    $output['NOTA1'][]        = $nota1;
                    $output['EST_NOTA1'][]    = $estNota1;
                    $output['NOTA2'][]        = $nota2;
                    $output['EST_NOTA2'][]    = $estNota2;
                    $output['NOTA3'][]        = $nota3;
                    $output['EST_NOTA3'][]    = $estNota3;
                    $output['EXA'][]          = $exa;
                    $output['EST_EXA'][]      = $estExa;
                    $output['HAB'][]          = 0.0;
                    $output['EST_HAB'][]      = 'S';
                    $output['OPC'][]          = 0.0;
                    $output['EST_OPC'][]      = 'S';
                    $output['CLASES'][]       = array(
                        'ID'          => array((int)$c['id']),
                        'FECHA_INI'   => array('Aula Virtual Activa'),
                        'URL_REUNION' => array('http://localhost:10000/course/view.php?id=' . $c['id'])
                    );
                }
            } elseif (!empty($subnotasMap)) {
                // Si Moodle no respondió pero hay notas en Oracle SUBNOTAS
                $output = array(
                    'CODCARMAT'  => array(),
                    'CODMAT'     => array(),
                    'CODMATMAT'  => array(),
                    'NOMBREMAT'  => array(),
                    'GRUPO'      => array(),
                    'CREDITOS'   => array(),
                    'SEMESTRE'   => array(),
                    'DEF'        => array(),
                    'ID_MICRO'   => array(),
                    'CONTENIDO'  => array(),
                    'BIBLIOGRAFIA' => array(),
                    'EMAIL'      => array(),
                    'SECCIONAL'  => array(),
                    'RUBRICA'    => array(),
                    'NOTA1'      => array(),
                    'EST_NOTA1'  => array(),
                    'NOTA2'      => array(),
                    'EST_NOTA2'  => array(),
                    'NOTA3'      => array(),
                    'EST_NOTA3'  => array(),
                    'EXA'        => array(),
                    'EST_EXA'    => array(),
                    'HAB'        => array(),
                    'EST_HAB'    => array(),
                    'OPC'        => array(),
                    'EST_OPC'    => array(),
                    'CLASES'     => array()
                );

                foreach ($subnotasMap as $m) {
                    $codMat = $m['COD_MATERIA'];
                    $codCarrera = strlen($codMat) >= 7 ? substr($codMat, 0, 3) : substr($codigo, 0, 3);
                    $codMatPura = strlen($codMat) >= 7 ? substr($codMat, 3) : $codMat;

                    $def = 0.0;
                    $cnt = 0;
                    if ($m['EST_NOTA1'] === 'V') { $def += $m['NOTA1'] * 0.25; $cnt++; }
                    if ($m['EST_NOTA2'] === 'V') { $def += $m['NOTA2'] * 0.25; $cnt++; }
                    if ($m['EST_NOTA3'] === 'V') { $def += $m['NOTA3'] * 0.25; $cnt++; }
                    if ($m['EST_EXA'] === 'V')   { $def += $m['EXA'] * 0.25; $cnt++; }
                    if ($cnt > 0) {
                        $def = round($def / ($cnt * 0.25), 1);
                    }

                    $output['CODCARMAT'][]  = $codCarrera;
                    $output['CODMAT'][]     = $codMatPura;
                    $output['CODMATMAT'][]  = $codMatPura;
                    $output['NOMBREMAT'][]  = 'MATERIA ' . $codMatPura;
                    $output['GRUPO'][]      = $m['GRUPO'];
                    $output['CREDITOS'][]   = 3;
                    $output['SEMESTRE'][]   = 6;
                    $output['DEF'][]        = $def;
                    $output['ID_MICRO'][]   = 101;
                    $output['CONTENIDO'][]  = "Unidad 1: Introduccion\nUnidad 2: Desarrollo\nUnidad 3: Evaluacion";
                    $output['BIBLIOGRAFIA'][] = "Material disponible en la plataforma.";
                    $output['EMAIL'][]      = 'profesor@ufps.edu.co';
                    $output['SECCIONAL'][]  = '01';
                    $output['RUBRICA'][]    = 1;
                    $output['NOTA1'][]      = $m['NOTA1'];
                    $output['EST_NOTA1'][]  = $m['EST_NOTA1'];
                    $output['NOTA2'][]      = $m['NOTA2'];
                    $output['EST_NOTA2'][]  = $m['EST_NOTA2'];
                    $output['NOTA3'][]      = $m['NOTA3'];
                    $output['EST_NOTA3'][]  = $m['EST_NOTA3'];
                    $output['EXA'][]        = $m['EXA'];
                    $output['EST_EXA'][]    = $m['EST_EXA'];
                    $output['HAB'][]        = 0.0;
                    $output['EST_HAB'][]    = 'S';
                    $output['OPC'][]        = 0.0;
                    $output['EST_OPC'][]    = 'S';
                    $output['CLASES'][]     = array('ID' => array(1), 'FECHA_INI' => array('2026-02-15 08:00'), 'URL_REUNION' => array('https://meet.google.com/abc-defg-hij'));
                }
            }
        }

        // Fallback final para pruebas si no existe la vista en Oracle, ni Moodle ni SUBNOTAS
        if (empty($output) || !isset($output['CODCARMAT']) || empty($output['CODCARMAT'])) {
            $output = array(
                'CODCARMAT'  => array('115', '115'),
                'CODMAT'     => array('5304', '5305'),
                'CODMATMAT'  => array('5304', '5305'),
                'NOMBREMAT'  => array('ANALISIS Y DISENO DE SISTEMAS', 'ARQUITECTURA DE SOFTWARE'),
                'GRUPO'      => array('A', 'B'),
                'CREDITOS'   => array(3, 4),
                'SEMESTRE'   => array(6, 6),
                'DEF'        => array(3.8, 4.1),
                'ID_MICRO'   => array(101, 102),
                'CONTENIDO'  => array(
                    "Unidad 1: Requerimientos\nUnidad 2: Diagramas UML\nUnidad 3: Arquitectura Limpia",
                    "Unidad 1: Patrones de Diseno\nUnidad 2: Microservicios"
                ),
                'BIBLIOGRAFIA' => array(
                    "Sommerville, Software Engineering 10th Ed.",
                    "Martin Fowler, Patterns of Enterprise Application Architecture"
                ),
                'EMAIL'      => array('profesor@ufps.edu.co', 'docente.arq@ufps.edu.co'),
                'SECCIONAL'  => array('01', '01'),
                'RUBRICA'    => array(1, 1),
                'NOTA1'      => array(4.0, 4.5),
                'EST_NOTA1'  => array('V', 'V'),
                'NOTA2'      => array(3.5, 3.8),
                'EST_NOTA2'  => array('V', 'V'),
                'NOTA3'      => array(0.0, 0.0),
                'EST_NOTA3'  => array('S', 'S'),
                'EXA'        => array(0.0, 0.0),
                'EST_EXA'    => array('S', 'S'),
                'HAB'        => array(0.0, 0.0),
                'EST_HAB'    => array('S', 'S'),
                'OPC'        => array(0.0, 0.0),
                'EST_OPC'    => array('S', 'S'),
                'CLASES'     => array(
                    array('ID' => array(1), 'FECHA_INI' => array('2026-02-15 08:00'), 'URL_REUNION' => array('https://meet.google.com/abc-defg-hij')),
                    array('ID' => array(1), 'FECHA_INI' => array('2026-02-16 10:00'), 'URL_REUNION' => array('https://meet.google.com/klm-nopq-rst'))
                )
            );
        }

        return $output;
    }

    public function codigo_proyecto($cod_carrera, $cod_alumno) {
        $obj = null;
        $sql = "select p.cod_materia codigo_proyecto "
            . "from sia.alumno a, sia.pensum p "
            . "where a.cod_carrera = p.cod_carrera and "
            . "a.pensum = p.tipo_pensum and "
            . "a.cod_carrera = '{$cod_carrera}' and "
            . "a.cod_alumno = '{$cod_alumno}'";

        if (isset($this->database2)) {
            $this->database2->get_sql_object($sql, $obj);
        }
        return $obj;
    }
}

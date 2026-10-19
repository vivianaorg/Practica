<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calculo_notas_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Moodle_model');
        $this->load->model('Subnotas_model');
        $this->load->model('Rubrica_model');
    }

    /**
     * Parsea el código del curso (ej: '1155304-A' o '1155304') en código de materia y grupo.
     *
     * @param string $cursoCodigo
     * @param int|string|null $courseId ID del curso en Moodle para fallback seguro
     * @return array ['cod_materia' => string, 'grupo' => string]
     */
    public function parsear_curso_codigo($cursoCodigo, $courseId = null)
    {
        $defaultMateria = !empty($courseId) ? 'CURSO_' . $courseId : '1155304';
        $codMateria = !empty($cursoCodigo) ? trim($cursoCodigo) : $defaultMateria;
        $grupo      = '-';

        if (!empty($cursoCodigo)) {
            if (strpos($cursoCodigo, '-') !== false) {
                $partes = explode('-', $cursoCodigo, 2);
                $codMateria = trim($partes[0]);
                $grupo      = trim($partes[1]);
            } else {
                $codMateria = trim($cursoCodigo);
                $grupo      = '-';
            }
        }

        return array(
            'cod_materia' => $codMateria,
            'grupo'       => (!empty($grupo) && $grupo !== '') ? $grupo : '-'
        );
    }

    /**
     * Procesa y calcula los datos completos de evaluación para un corte específico.
     *
     * @param int|string $courseId
     * @param string $tipoPrevio
     * @param string $codProfesor
     * @param string $semestre
     * @return array
     */
    public function obtener_datos_corte($courseId, $tipoPrevio, $codProfesor, $semestre)
    {
        $resultadoMoodle = $this->Moodle_model->obtener_calificaciones($courseId);
        $exitoMoodle = isset($resultadoMoodle['exito']) ? $resultadoMoodle['exito'] : false;
        $mensajeMoodle = isset($resultadoMoodle['mensaje']) ? $resultadoMoodle['mensaje'] : '';
        $cursoNombre = isset($resultadoMoodle['datos']['curso_nombre']) ? $resultadoMoodle['datos']['curso_nombre'] : 'Curso Moodle #' . $courseId;
        $cursoCodigo = isset($resultadoMoodle['datos']['curso_codigo']) ? $resultadoMoodle['datos']['curso_codigo'] : '';
        $estudiantesMoodle = isset($resultadoMoodle['datos']['estudiantes']) ? $resultadoMoodle['datos']['estudiantes'] : array();

        $infoCurso = $this->parsear_curso_codigo($cursoCodigo, $courseId);
        $codMateria = $infoCurso['cod_materia'];
        $grupo      = $infoCurso['grupo'];

        $rubrica = $this->Rubrica_model->obtener($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
        if (empty($rubrica)) {
            $rubrica = $this->Subnotas_model->obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
        }
        $todasRubricas = $this->Rubrica_model->obtener_todas_del_curso($codProfesor, $codMateria, $grupo, $semestre);
        if (empty($todasRubricas) || (empty($todasRubricas['1']) && empty($todasRubricas['2']) && empty($todasRubricas['3']) && empty($todasRubricas['FINAL']))) {
            $todasRubricas = $this->Subnotas_model->obtener_todas_rubricas_curso($codProfesor, $codMateria, $grupo, $semestre);
        }

        $sumaPorcentajes = 0;
        if (!empty($rubrica)) {
            foreach ($rubrica as $r) {
                $sumaPorcentajes += isset($r->PORCENTAJE) ? (float)$r->PORCENTAJE : 0;
            }
        }
        $rubricaValida = (!empty($rubrica) && abs($sumaPorcentajes - 100) < 0.01);

        $subnotasGuardadas = $this->Subnotas_model->obtener_todas_subnotas_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        $estudiantesCalculados = array();
        $totalAprobados = 0;
        $totalReprobados = 0;
        $acumuladorPromedio = 0;

        foreach ($estudiantesMoodle as $est) {
            $username = isset($est['username']) ? $est['username'] : '';
            $codEstudiante = !empty($est['codigo']) ? $est['codigo'] : (!empty($est['idnumber']) ? $est['idnumber'] : $username);
            if ($username === 'admin' || $codEstudiante === 'admin' || (isset($est['user_id']) && (int)$est['user_id'] === 2)) {
                continue;
            }
            $nombreCompleto = !empty($est['nombre_completo']) ? $est['nombre_completo'] : (trim($est['nombres'] . ' ' . $est['apellidos']));

            $calificacionesPorId = array();
            $calificacionesPorNombre = array();

            if (!empty($est['calificaciones']) && is_array($est['calificaciones'])) {
                foreach ($est['calificaciones'] as $cal) {
                    if (isset($cal['cmid']) && (int)$cal['cmid'] > 0) {
                        $calificacionesPorId[(int)$cal['cmid']] = $cal;
                    }
                    if (isset($cal['id_actividad']) && (int)$cal['id_actividad'] > 0) {
                        $calificacionesPorId[(int)$cal['id_actividad']] = $cal;
                    }
                    if (isset($cal['grade_item_id']) && (int)$cal['grade_item_id'] > 0) {
                        $calificacionesPorId[(int)$cal['grade_item_id']] = $cal;
                    }

                    $nomActCal = isset($cal['nombre_actividad']) ? $cal['nombre_actividad'] : '';
                    $nomNorm = strtolower(trim($nomActCal));
                    if (!empty($nomNorm)) {
                        $calificacionesPorNombre[$nomNorm] = $cal;
                    }
                }
            }

            $yaGuardado = isset($subnotasGuardadas[$codEstudiante]);
            $desglose = array();
            $notaSugerida = 0.0;

            if ($rubricaValida) {
                foreach ($rubrica as $itemRubrica) {
                    $idAct   = (int)$itemRubrica->ID_ACTIVIDAD_MOODLE;
                    $nomAct  = trim($itemRubrica->NOMBRE_ACTIVIDAD);
                    $nomNorm = strtolower($nomAct);
                    $pct     = (float)$itemRubrica->PORCENTAJE;
                    $tipoAct = isset($itemRubrica->TIPO_ACTIVIDAD) ? trim($itemRubrica->TIPO_ACTIVIDAD) : '';
                    $esManual = ($idAct === 0 || strtolower($tipoAct) === 'manual');

                    $notaOriginal = 0.0;
                    $notaMax = 5.0;
                    $presento = false;

                    if ($esManual) {
                        if ($yaGuardado && isset($subnotasGuardadas[$codEstudiante]['items'])) {
                            foreach ($subnotasGuardadas[$codEstudiante]['items'] as $sItem) {
                                $sNom = isset($sItem->NOMBRE_ACTIVIDAD) ? trim($sItem->NOMBRE_ACTIVIDAD) : '';
                                if (strcasecmp($sNom, $nomAct) === 0 || ((int)$sItem->ID_ACTIVIDAD_MOODLE === 0 && (int)$idAct === 0 && count($rubrica) === 1)) {
                                    $notaOriginal = isset($sItem->NOTA_ORIGINAL) ? (float)$sItem->NOTA_ORIGINAL : 0.0;
                                    $notaMax = isset($sItem->NOTA_MAXIMA) ? (float)$sItem->NOTA_MAXIMA : 5.0;
                                    $presento = ($notaOriginal > 0);
                                    break;
                                }
                            }
                        }
                    } else {
                        $calEncontrada = null;
                        if (isset($calificacionesPorId[$idAct])) {
                            $calEncontrada = $calificacionesPorId[$idAct];
                        } elseif (isset($calificacionesPorNombre[$nomNorm])) {
                            $calEncontrada = $calificacionesPorNombre[$nomNorm];
                        } else {
                            foreach ($calificacionesPorNombre as $calNom => $calObj) {
                                if (strpos($calNom, $nomNorm) !== false || strpos($nomNorm, $calNom) !== false) {
                                    $calEncontrada = $calObj;
                                    break;
                                }
                            }
                        }

                        if ($calEncontrada !== null) {
                            $presento = isset($calEncontrada['presento']) ? (bool)$calEncontrada['presento'] : true;
                            $notaOriginal = (float)$calEncontrada['nota_final'];
                            $notaMax = (float)$calEncontrada['nota_maxima'];
                        }
                    }

                    if ($notaMax > 0 && abs($notaMax - 5.0) > 0.01) {
                        $notaNormalizada = round(($notaOriginal / $notaMax) * 5.0, 2);
                    } else {
                        $notaNormalizada = $notaOriginal;
                    }

                    $subnota = round($notaNormalizada * ($pct / 100), 2);
                    $notaSugerida += $subnota;

                    $desglose[] = array(
                        'id_actividad_moodle' => $idAct,
                        'nombre_actividad'    => $nomAct,
                        'tipo_actividad'      => $tipoAct,
                        'es_manual'           => $esManual,
                        'nota_original'       => $notaOriginal,
                        'nota_maxima'         => $notaMax,
                        'nota_normalizada'    => $notaNormalizada,
                        'porcentaje'          => $pct,
                        'subnota'             => $subnota,
                        'presento'            => $presento,
                    );
                }
            }

            $notaSugerida = min(5.0, max(0.0, round($notaSugerida, 2)));

            $estadoActual = 'SUGERIDA';
            $notaDefinitiva = $notaSugerida;

            if ($yaGuardado) {
                $estadoGuardado = $subnotasGuardadas[$codEstudiante]['estado'];
                if ($estadoGuardado === 'ACEPTADA' || $estadoGuardado === 'MODIFICADA') {
                    $estadoActual = $estadoGuardado;
                    $notaDefinitiva = round($subnotasGuardadas[$codEstudiante]['total_nota'], 2);
                } else {
                    $estadoActual = 'SUGERIDA';
                    $notaDefinitiva = $notaSugerida;
                }
            }

            if ($notaDefinitiva >= 3.0) {
                $totalAprobados++;
            } else {
                $totalReprobados++;
            }
            $acumuladorPromedio += $notaDefinitiva;

            $estudiantesCalculados[] = array(
                'user_id'          => isset($est['user_id']) ? $est['user_id'] : 0,
                'codigo'           => $codEstudiante,
                'nombre_completo'  => $nombreCompleto,
                'email'            => isset($est['email']) ? $est['email'] : '',
                'nota_sugerida'    => $notaSugerida,
                'nota_definitiva'  => $notaDefinitiva,
                'estado'           => $estadoActual,
                'ya_guardado'      => $yaGuardado,
                'desglose'         => $desglose,
            );
        }

        $totalEstudiantes = count($estudiantesCalculados);
        $promedioGrupo = ($totalEstudiantes > 0) ? round($acumuladorPromedio / $totalEstudiantes, 2) : 0.0;

        $corteCalificado = $this->Subnotas_model->corte_esta_calificado($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);

        return array(
            'course_id'          => $courseId,
            'tipo_previo'        => $tipoPrevio,
            'curso_nombre'       => $cursoNombre,
            'curso_codigo'       => $cursoCodigo,
            'cod_profesor'       => $codProfesor,
            'cod_materia'        => $codMateria,
            'grupo'              => $grupo,
            'semestre'           => $semestre,
            'rubrica'            => $rubrica,
            'todas_rubricas'     => $todasRubricas,
            'rubrica_valida'     => $rubricaValida,
            'suma_porcentajes'   => $sumaPorcentajes,
            'exito_moodle'       => $exitoMoodle,
            'mensaje_moodle'     => $mensajeMoodle,
            'estudiantes'        => $estudiantesCalculados,
            'total_estudiantes'  => $totalEstudiantes,
            'total_aprobados'    => $totalAprobados,
            'total_reprobados'   => $totalReprobados,
            'promedio_grupo'     => $promedioGrupo,
            'corte_calificado'   => $corteCalificado,
        );
    }

    /**
     * Procesa y calcula la planilla de notas general consolidando todos los cortes evaluativos.
     *
     * @param int|string $courseId
     * @param string $codProfesor
     * @param string $semestre
     * @return array
     */
    public function obtener_planilla_general($courseId, $codProfesor, $semestre)
    {
        $this->load->model('Moodle_model');
        $this->load->model('Subnotas_model');
        $this->load->model('Rubrica_model');

        $resultadoMoodle = $this->Moodle_model->obtener_calificaciones($courseId);
        $exitoMoodle = isset($resultadoMoodle['exito']) ? $resultadoMoodle['exito'] : false;
        $mensajeMoodle = isset($resultadoMoodle['mensaje']) ? $resultadoMoodle['mensaje'] : '';
        $cursoNombre = isset($resultadoMoodle['datos']['curso_nombre']) ? $resultadoMoodle['datos']['curso_nombre'] : 'Curso Moodle #' . $courseId;
        $cursoCodigo = isset($resultadoMoodle['datos']['curso_codigo']) ? $resultadoMoodle['datos']['curso_codigo'] : '';
        $estudiantesMoodle = isset($resultadoMoodle['datos']['estudiantes']) ? $resultadoMoodle['datos']['estudiantes'] : array();

        $infoCurso = $this->parsear_curso_codigo($cursoCodigo, $courseId);
        $codMateria = $infoCurso['cod_materia'];
        $grupo      = $infoCurso['grupo'];

        $calificacionesGuardadas = $this->Subnotas_model->obtener_calificaciones_todos_cortes($codProfesor, $codMateria, $grupo, $semestre);
        $subnotasDetalle = $this->Subnotas_model->obtener_todas_subnotas_curso($codProfesor, $codMateria, $grupo, $semestre);
        $todasRubricas = $this->Rubrica_model->obtener_todas_del_curso($codProfesor, $codMateria, $grupo, $semestre);

        $pesosCortes = array(
            '1'     => 23.3,
            '2'     => 23.3,
            '3'     => 23.4,
            'FINAL' => 30.0,
        );

        $mapaPalabrasCortes = array(
            '1'     => array('primer previo', '1er previo', '1p', 'previo 1', 'corte 1', 'evaluacion 1', 'quiz 1', 'parcial 1', 'tema 1', 'actividad 1', 'taller 1'),
            '2'     => array('segundo previo', '2do previo', '2p', 'previo 2', 'corte 2', 'evaluacion 2', 'quiz 2', 'parcial 2', 'tema 2', 'actividad 2', 'taller 2'),
            '3'     => array('tercer previo', '3er previo', '3p', 'previo 3', 'corte 3', 'evaluacion 3', 'quiz 3', 'parcial 3', 'tema 3', 'actividad 3', 'taller 3'),
            'FINAL' => array('examen final', 'evaluacion final', 'final', 'ex final', 'parcial final', 'evaluacion sumativa', 'proyecto final')
        );

        $cortesClaves = array('1', '2', '3', 'FINAL');
        $planilla = array();
        $totalAprobados = 0;
        $totalReprobados = 0;
        $sumaDefinitivas = 0;
        $conNotaDefinitiva = 0;

        foreach ($estudiantesMoodle as $est) {
            $username = isset($est['username']) ? $est['username'] : '';
            $codEstudiante = !empty($est['codigo']) ? $est['codigo'] : (!empty($est['idnumber']) ? $est['idnumber'] : $username);
            if ($username === 'admin' || $codEstudiante === 'admin' || (isset($est['user_id']) && (int)$est['user_id'] === 2)) {
                continue;
            }
            $nombreCompleto = !empty($est['nombre_completo']) ? $est['nombre_completo'] : (trim($est['nombres'] . ' ' . $est['apellidos']));

            $posiblesClaves = array(
                trim($codEstudiante),
                trim($username),
                isset($est['idnumber']) ? trim($est['idnumber']) : '',
                isset($est['codigo']) ? trim($est['codigo']) : ''
            );

            $cortesEst = array();
            foreach ($posiblesClaves as $clave) {
                if (!empty($clave) && isset($calificacionesGuardadas[$clave])) {
                    $cortesEst = $calificacionesGuardadas[$clave];
                    break;
                }
            }
            if (empty($cortesEst)) {
                foreach ($posiblesClaves as $clave) {
                    if (empty($clave)) continue;
                    $claveLower = strtolower($clave);
                    foreach ($calificacionesGuardadas as $guardadoCod => $guardadoVal) {
                        if (strtolower(trim($guardadoCod)) === $claveLower) {
                            $cortesEst = $guardadoVal;
                            break 2;
                        }
                    }
                }
            }

            $desgloseOracle = array();
            foreach ($posiblesClaves as $clave) {
                if (!empty($clave) && isset($subnotasDetalle[$clave])) {
                    $desgloseOracle = $subnotasDetalle[$clave];
                    break;
                }
            }
            if (empty($desgloseOracle)) {
                foreach ($posiblesClaves as $clave) {
                    if (empty($clave)) continue;
                    $claveLower = strtolower($clave);
                    foreach ($subnotasDetalle as $guardadoCod => $guardadoVal) {
                        if (strtolower(trim($guardadoCod)) === $claveLower) {
                            $desgloseOracle = $guardadoVal;
                            break 2;
                        }
                    }
                }
            }

            $moodleActs = isset($est['calificaciones']) && is_array($est['calificaciones']) ? $est['calificaciones'] : array();
            $calificacionesPorId = array();
            $calificacionesPorNombre = array();

            foreach ($moodleActs as $cal) {
                if (isset($cal['cmid']) && (int)$cal['cmid'] > 0) {
                    $calificacionesPorId[(int)$cal['cmid']] = $cal;
                }
                if (isset($cal['id_actividad']) && (int)$cal['id_actividad'] > 0) {
                    $calificacionesPorId[(int)$cal['id_actividad']] = $cal;
                }
                if (isset($cal['grade_item_id']) && (int)$cal['grade_item_id'] > 0) {
                    $calificacionesPorId[(int)$cal['grade_item_id']] = $cal;
                }
                $nomActCal = isset($cal['nombre_actividad']) ? $cal['nombre_actividad'] : '';
                $nomNorm = strtolower(trim($nomActCal));
                if (!empty($nomNorm)) {
                    $calificacionesPorNombre[$nomNorm] = $cal;
                }
            }

            $notasCortes = array(
                '1'     => null,
                '2'     => null,
                '3'     => null,
                'FINAL' => null,
            );
            $desgloseFinal = array(
                '1'     => array(),
                '2'     => array(),
                '3'     => array(),
                'FINAL' => array(),
            );

            foreach ($cortesClaves as $cClave) {
                if (!empty($desgloseOracle[$cClave])) {
                    $itemsOra = array();
                    $sumaOra = 0.0;
                    foreach ($desgloseOracle[$cClave] as $rowOra) {
                        $nom = isset($rowOra->NOMBRE_ACTIVIDAD) ? $rowOra->NOMBRE_ACTIVIDAD : '';
                        $orig = isset($rowOra->NOTA_ORIGINAL) ? (float)$rowOra->NOTA_ORIGINAL : 0.0;
                        $max = isset($rowOra->NOTA_MAXIMA) ? (float)$rowOra->NOTA_MAXIMA : 5.0;
                        $pct = isset($rowOra->PORCENTAJE) ? (float)$rowOra->PORCENTAJE : 0.0;
                        $sub = isset($rowOra->SUBNOTA) ? (float)$rowOra->SUBNOTA : 0.0;
                        $sumaOra += $sub;

                        $itemsOra[] = array(
                            'ID_ACTIVIDAD_MOODLE' => isset($rowOra->ID_ACTIVIDAD_MOODLE) ? $rowOra->ID_ACTIVIDAD_MOODLE : 0,
                            'id_actividad_moodle' => isset($rowOra->ID_ACTIVIDAD_MOODLE) ? $rowOra->ID_ACTIVIDAD_MOODLE : 0,
                            'NOMBRE_ACTIVIDAD'    => $nom,
                            'nombre_actividad'    => $nom,
                            'NOTA_ORIGINAL'       => $orig,
                            'nota_original'       => $orig,
                            'NOTA_MAXIMA'         => $max,
                            'nota_maxima'         => $max,
                            'PORCENTAJE'          => $pct,
                            'porcentaje'          => $pct,
                            'SUBNOTA'             => $sub,
                            'subnota'             => $sub,
                            'ESTADO'              => isset($rowOra->ESTADO) ? $rowOra->ESTADO : 'ORACLE'
                        );
                    }
                    $desgloseFinal[$cClave] = $itemsOra;
                    $notasCortes[$cClave] = (isset($cortesEst[$cClave]) && isset($cortesEst[$cClave]['nota']))
                        ? (float)$cortesEst[$cClave]['nota']
                        : round($sumaOra, 2);
                    continue;
                }

                $rubricaCorte = isset($todasRubricas[$cClave]) ? $todasRubricas[$cClave] : array();
                if (!empty($rubricaCorte)) {
                    $itemsMoodleRub = array();
                    $notaSug = 0.0;

                    foreach ($rubricaCorte as $rItem) {
                        $idAct = (int)$rItem->ID_ACTIVIDAD_MOODLE;
                        $nomAct = trim($rItem->NOMBRE_ACTIVIDAD);
                        $nomNorm = strtolower($nomAct);
                        $pct = (float)$rItem->PORCENTAJE;
                        $tipoAct = isset($rItem->TIPO_ACTIVIDAD) ? trim($rItem->TIPO_ACTIVIDAD) : '';
                        $esManual = ($idAct === 0 || strtolower($tipoAct) === 'manual');

                        $notaOrig = 0.0;
                        $notaMax = 5.0;

                        if (!$esManual) {
                            $calEncontrada = null;
                            if (isset($calificacionesPorId[$idAct])) {
                                $calEncontrada = $calificacionesPorId[$idAct];
                            } elseif (isset($calificacionesPorNombre[$nomNorm])) {
                                $calEncontrada = $calificacionesPorNombre[$nomNorm];
                            } else {
                                foreach ($calificacionesPorNombre as $calNom => $calObj) {
                                    if (strpos($calNom, $nomNorm) !== false || strpos($nomNorm, $calNom) !== false) {
                                        $calEncontrada = $calObj;
                                        break;
                                    }
                                }
                            }

                            if ($calEncontrada !== null) {
                                $notaOrig = (float)$calEncontrada['nota_final'];
                                $notaMax = (float)$calEncontrada['nota_maxima'];
                            }
                        }

                        $notaNorm = ($notaMax > 0 && abs($notaMax - 5.0) > 0.01) ? round(($notaOrig / $notaMax) * 5.0, 2) : $notaOrig;
                        $subnota = round($notaNorm * ($pct / 100), 2);
                        $notaSug += $subnota;

                        $itemsMoodleRub[] = array(
                            'ID_ACTIVIDAD_MOODLE' => $idAct,
                            'id_actividad_moodle' => $idAct,
                            'NOMBRE_ACTIVIDAD'    => $nomAct,
                            'nombre_actividad'    => $nomAct,
                            'NOTA_ORIGINAL'       => $notaOrig,
                            'nota_original'       => $notaOrig,
                            'NOTA_MAXIMA'         => $notaMax,
                            'nota_maxima'         => $notaMax,
                            'PORCENTAJE'          => $pct,
                            'porcentaje'          => $pct,
                            'SUBNOTA'             => $subnota,
                            'subnota'             => $subnota,
                            'ESTADO'              => 'MOODLE'
                        );
                    }

                    if (!empty($itemsMoodleRub)) {
                        $desgloseFinal[$cClave] = $itemsMoodleRub;
                        $notasCortes[$cClave] = min(5.0, max(0.0, round($notaSug, 2)));
                        continue;
                    }
                }

                $actsMatching = array();
                if (!empty($moodleActs) && isset($mapaPalabrasCortes[$cClave])) {
                    foreach ($moodleActs as $mAct) {
                        $mNom = strtolower(trim(isset($mAct['nombre_actividad']) ? $mAct['nombre_actividad'] : ''));
                        foreach ($mapaPalabrasCortes[$cClave] as $kw) {
                            if (strpos($mNom, $kw) !== false) {
                                $actsMatching[] = $mAct;
                                break;
                            }
                        }
                    }
                }

                if (!empty($actsMatching)) {
                    $totCount = count($actsMatching);
                    $porcEach = $totCount > 0 ? round(100.0 / $totCount, 1) : 0;
                    $itemsMoodleDyn = array();
                    $notaSugDyn = 0.0;

                    foreach ($actsMatching as $mAct) {
                        $notaOrig = isset($mAct['nota_final']) ? (float)$mAct['nota_final'] : 0.0;
                        $notaMax  = isset($mAct['nota_maxima']) ? (float)$mAct['nota_maxima'] : 5.0;
                        $notaNorm = ($notaMax > 0 && abs($notaMax - 5.0) > 0.01) ? round(($notaOrig / $notaMax) * 5.0, 2) : $notaOrig;
                        $subnota  = round($notaNorm * ($porcEach / 100.0), 2);
                        $notaSugDyn += $subnota;

                        $itemsMoodleDyn[] = array(
                            'ID_ACTIVIDAD_MOODLE' => isset($mAct['id_actividad']) ? (int)$mAct['id_actividad'] : (isset($mAct['cmid']) ? (int)$mAct['cmid'] : 0),
                            'id_actividad_moodle' => isset($mAct['id_actividad']) ? (int)$mAct['id_actividad'] : (isset($mAct['cmid']) ? (int)$mAct['cmid'] : 0),
                            'NOMBRE_ACTIVIDAD'    => isset($mAct['nombre_actividad']) ? $mAct['nombre_actividad'] : 'Actividad Moodle',
                            'nombre_actividad'    => isset($mAct['nombre_actividad']) ? $mAct['nombre_actividad'] : 'Actividad Moodle',
                            'NOTA_ORIGINAL'       => $notaOrig,
                            'nota_original'       => $notaOrig,
                            'NOTA_MAXIMA'         => $notaMax,
                            'nota_maxima'         => $notaMax,
                            'PORCENTAJE'          => $porcEach,
                            'porcentaje'          => $porcEach,
                            'SUBNOTA'             => $subnota,
                            'subnota'             => $subnota,
                            'ESTADO'              => 'MOODLE'
                        );
                    }

                    $desgloseFinal[$cClave] = $itemsMoodleDyn;
                    $notasCortes[$cClave] = min(5.0, max(0.0, round($notaSugDyn, 2)));
                }
            }

            $algunCorteTieneDatos = false;
            foreach ($cortesClaves as $cc) {
                if (!empty($desgloseFinal[$cc])) {
                    $algunCorteTieneDatos = true;
                    break;
                }
            }
            if (!$algunCorteTieneDatos && !empty($moodleActs)) {
                $totCount = count($moodleActs);
                $porcEach = $totCount > 0 ? round(100.0 / $totCount, 1) : 0;
                $itemsGen = array();
                $sumGen = 0.0;
                foreach ($moodleActs as $mAct) {
                    $notaOrig = isset($mAct['nota_final']) ? (float)$mAct['nota_final'] : 0.0;
                    $notaMax  = isset($mAct['nota_maxima']) ? (float)$mAct['nota_maxima'] : 5.0;
                    $notaNorm = ($notaMax > 0 && abs($notaMax - 5.0) > 0.01) ? round(($notaOrig / $notaMax) * 5.0, 2) : $notaOrig;
                    $subnota  = round($notaNorm * ($porcEach / 100.0), 2);
                    $sumGen += $subnota;

                    $itemsGen[] = array(
                        'ID_ACTIVIDAD_MOODLE' => isset($mAct['id_actividad']) ? (int)$mAct['id_actividad'] : (isset($mAct['cmid']) ? (int)$mAct['cmid'] : 0),
                        'id_actividad_moodle' => isset($mAct['id_actividad']) ? (int)$mAct['id_actividad'] : (isset($mAct['cmid']) ? (int)$mAct['cmid'] : 0),
                        'NOMBRE_ACTIVIDAD'    => isset($mAct['nombre_actividad']) ? $mAct['nombre_actividad'] : 'Actividad Moodle',
                        'nombre_actividad'    => isset($mAct['nombre_actividad']) ? $mAct['nombre_actividad'] : 'Actividad Moodle',
                        'NOTA_ORIGINAL'       => $notaOrig,
                        'nota_original'       => $notaOrig,
                        'NOTA_MAXIMA'         => $notaMax,
                        'nota_maxima'         => $notaMax,
                        'PORCENTAJE'          => $porcEach,
                        'porcentaje'          => $porcEach,
                        'SUBNOTA'             => $subnota,
                        'subnota'             => $subnota,
                        'ESTADO'              => 'MOODLE'
                    );
                }
                $desgloseFinal['1'] = $itemsGen;
                $notasCortes['1'] = min(5.0, max(0.0, round($sumGen, 2)));
            }

            $n1 = $notasCortes['1'];
            $n2 = $notasCortes['2'];
            $n3 = $notasCortes['3'];
            $nf = $notasCortes['FINAL'];

            $acumulado = 0.0;
            $pesoEvaluado = 0.0;

            if ($n1 !== null) {
                $acumulado += $n1 * ($pesosCortes['1'] / 100);
                $pesoEvaluado += $pesosCortes['1'];
            }
            if ($n2 !== null) {
                $acumulado += $n2 * ($pesosCortes['2'] / 100);
                $pesoEvaluado += $pesosCortes['2'];
            }
            if ($n3 !== null) {
                $acumulado += $n3 * ($pesosCortes['3'] / 100);
                $pesoEvaluado += $pesosCortes['3'];
            }
            if ($nf !== null) {
                $acumulado += $nf * ($pesosCortes['FINAL'] / 100);
                $pesoEvaluado += $pesosCortes['FINAL'];
            }

            $definitiva = round($acumulado, 2);
            $estadoAcademico = 'EN_CURSO';

            if ($pesoEvaluado >= 99.0) {
                $estadoAcademico = ($definitiva >= 3.0) ? 'APROBADO' : 'REPROBADO';
            } elseif ($pesoEvaluado > 0) {
                $estadoAcademico = ($definitiva >= 3.0) ? 'APROBANDO' : 'EN_RIESGO';
            }

            if ($definitiva >= 3.0 && $pesoEvaluado > 0) {
                $totalAprobados++;
            } elseif ($pesoEvaluado > 0) {
                $totalReprobados++;
            }

            if ($pesoEvaluado > 0) {
                $sumaDefinitivas += $definitiva;
                $conNotaDefinitiva++;
            }

            $planilla[] = array(
                'codigo'           => $codEstudiante,
                'nombre_completo'  => $nombreCompleto,
                'email'            => isset($est['email']) ? $est['email'] : '',
                'corte_1'          => $n1,
                'corte_2'          => $n2,
                'corte_3'          => $n3,
                'corte_final'      => $nf,
                'peso_evaluado'    => round($pesoEvaluado, 1),
                'definitiva'       => ($pesoEvaluado > 0) ? $definitiva : null,
                'estado_academico' => $estadoAcademico,
                'desglose'         => $desgloseFinal,
            );
        }

        $totalEstudiantes = count($planilla);
        $promedioGeneral = ($conNotaDefinitiva > 0) ? round($sumaDefinitivas / $conNotaDefinitiva, 2) : 0.0;

        return array(
            'course_id'          => $courseId,
            'curso_nombre'       => $cursoNombre,
            'curso_codigo'       => $cursoCodigo,
            'cod_profesor'       => $codProfesor,
            'cod_materia'        => $codMateria,
            'grupo'              => $grupo,
            'semestre'           => $semestre,
            'pesos_cortes'       => $pesosCortes,
            'planilla'           => $planilla,
            'total_estudiantes'  => $totalEstudiantes,
            'total_aprobados'    => $totalAprobados,
            'total_reprobados'   => $totalReprobados,
            'promedio_general'   => $promedioGeneral,
            'exito_moodle'       => $exitoMoodle,
            'mensaje_moodle'     => $mensajeMoodle,
        );
    }
}

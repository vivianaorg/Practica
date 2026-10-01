<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calculo_notas_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Moodle_model');
        $this->load->model('Subnotas_model');
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

        $rubrica = $this->Subnotas_model->obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio);
        $todasRubricas = $this->Subnotas_model->obtener_todas_rubricas_curso($codProfesor, $codMateria, $grupo, $semestre);

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
        $cortesCalificados = $this->Subnotas_model->obtener_cortes_calificados($codProfesor, $codMateria, $grupo, $semestre);

        $pesosCortes = array(
            '1'     => 23.3,
            '2'     => 23.3,
            '3'     => 23.4,
            'FINAL' => 30.0,
        );

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

            $desgloseEst = array();
            foreach ($posiblesClaves as $clave) {
                if (!empty($clave) && isset($subnotasDetalle[$clave])) {
                    $desgloseEst = $subnotasDetalle[$clave];
                    break;
                }
            }
            if (empty($desgloseEst)) {
                foreach ($posiblesClaves as $clave) {
                    if (empty($clave)) continue;
                    $claveLower = strtolower($clave);
                    foreach ($subnotasDetalle as $guardadoCod => $guardadoVal) {
                        if (strtolower(trim($guardadoCod)) === $claveLower) {
                            $desgloseEst = $guardadoVal;
                            break 2;
                        }
                    }
                }
            }

            $cortesClaves = array('1', '2', '3', 'FINAL');
            $notasCortes = array(
                '1'     => null,
                '2'     => null,
                '3'     => null,
                'FINAL' => null,
            );

            foreach ($cortesClaves as $cClave) {
                if (isset($cortesCalificados[$cClave]) && $cortesCalificados[$cClave]) {
                    if (isset($cortesEst[$cClave]) && isset($cortesEst[$cClave]['nota'])) {
                        $notasCortes[$cClave] = (float)$cortesEst[$cClave]['nota'];
                    }
                }
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

            $desgloseEst = isset($subnotasDetalle[$codEstudiante]) ? $subnotasDetalle[$codEstudiante] : array();

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
                'desglose'         => $desgloseEst,
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

<?php
define('CLI_SCRIPT', true);

// Desactivar visualizacion de errores y notices para proteger la salida JSON
error_reporting(0);
@ini_set('display_errors', '0');

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');

\core\cron::setup_user();

list($options, $unrecognized) = cli_get_params(
    array(
        'codigo' => '',
        'help'   => false
    ),
    array(
        'c' => 'codigo',
        'h' => 'help'
    )
);

if ($options['help'] || empty($options['codigo'])) {
    echo json_encode(array(
        'exito'   => false,
        'mensaje' => 'Parametro --codigo requerido'
    ));
    exit(1);
}

$codigo = trim($options['codigo']);

// Buscar estudiante por idnumber o username (case-insensitive)
$user = $DB->get_record_select(
    'user',
    "(LOWER(idnumber) = LOWER(:c1) OR LOWER(username) = LOWER(:c2)) AND deleted = 0",
    array('c1' => $codigo, 'c2' => $codigo)
);

if (!$user) {
    echo json_encode(array(
        'exito'   => false,
        'mensaje' => 'Estudiante con codigo ' . $codigo . ' no encontrado en Moodle'
    ));
    exit(0);
}

$userId = (int)$user->id;

// 1. Obtener cursos del estudiante usando la funcion nativa de Moodle enrol_get_all_users_courses
$courses = array();
try {
    if (function_exists('enrol_get_all_users_courses')) {
        // false = incluye todos los cursos independientemente de si ya comenzaron o son futuros
        $courses = enrol_get_all_users_courses($userId, false);
    }
} catch (Exception $e) {
    $courses = array();
}

// 2. Fallback SQL si la funcion nativa no retorno registros
if (empty($courses)) {
    try {
        $sqlCourses = "SELECT DISTINCT 
                           c.id, 
                           c.fullname, 
                           c.shortname, 
                           c.idnumber, 
                           c.summary
                       FROM {course} c
                       JOIN {enrol} e ON e.courseid = c.id
                       JOIN {user_enrolments} ue ON ue.enrolid = e.id
                       WHERE ue.userid = :userid AND c.id > 1
                       ORDER BY c.fullname ASC";
        $courses = $DB->get_records_sql($sqlCourses, array('userid' => $userId));
    } catch (Exception $e) {
        $courses = array();
    }
}

// 3. Fallback adicional por asignacion de roles en contexto de curso
if (empty($courses)) {
    try {
        $sqlRoles = "SELECT DISTINCT 
                         c.id, 
                         c.fullname, 
                         c.shortname, 
                         c.idnumber, 
                         c.summary
                     FROM {course} c
                     JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = 50)
                     JOIN {role_assignments} ra ON ra.contextid = ctx.id
                     WHERE ra.userid = :userid AND c.id > 1
                     ORDER BY c.fullname ASC";
        $courses = $DB->get_records_sql($sqlRoles, array('userid' => $userId));
    } catch (Exception $e) {
        $courses = array();
    }
}

$cursosData = array();

if (!empty($courses)) {
    foreach ($courses as $c) {
        $courseId = (int)$c->id;
        if ($courseId <= 1) {
            continue; // Ignorar el curso frontal del sitio
        }

        // Obtener profesor del curso si existe
        $profEmail = 'docente@ufps.edu.co';
        $profNombre = 'Docente Moodle';
        try {
            $teacherSql = "SELECT u.id, u.firstname, u.lastname, u.email
                           FROM {role_assignments} ra
                           JOIN {context} ctx ON ctx.id = ra.contextid
                           JOIN {user} u ON u.id = ra.userid
                           JOIN {role} r ON r.id = ra.roleid
                           WHERE ctx.contextlevel = 50 
                             AND ctx.instanceid = :courseid
                             AND (r.shortname = 'editingteacher' OR r.shortname = 'teacher')
                             AND u.deleted = 0
                           ORDER BY ra.id ASC";
            $teachers = $DB->get_records_sql($teacherSql, array('courseid' => $courseId));
            if (!empty($teachers)) {
                $t = reset($teachers);
                $profEmail = !empty($t->email) ? $t->email : 'docente@ufps.edu.co';
                $profNombre = trim($t->firstname . ' ' . $t->lastname);
            }
        } catch (Exception $e) {
            // Ignorar error al buscar profesor
        }

        // Obtener items de calificacion y notas del estudiante
        // El primer campo DEBE llamarse 'id' para cumplir con Moodle get_records_sql
        $actividades = array();
        $totalNotaMods = 0.0;
        $countModsGraded = 0;
        $courseTotalGrade = null;

        $nota1 = null;
        $nota2 = null;
        $nota3 = null;
        $exa   = null;

        try {
            $gradeSql = "SELECT 
                            gi.id,
                            gi.itemmodule,
                            gi.iteminstance,
                            gi.itemname,
                            gi.itemtype,
                            gi.grademax,
                            gi.grademin,
                            cm.id AS cmid,
                            gg.finalgrade,
                            gg.rawgrade,
                            gg.timemodified
                        FROM {grade_items} gi
                        LEFT JOIN {grade_grades} gg ON (gg.itemid = gi.id AND gg.userid = :userid)
                        LEFT JOIN {modules} m ON m.name = gi.itemmodule
                        LEFT JOIN {course_modules} cm ON (cm.course = gi.courseid AND cm.module = m.id AND cm.instance = gi.iteminstance)
                        WHERE gi.courseid = :courseid
                        ORDER BY gi.itemtype DESC, gi.sortorder ASC, gi.id ASC";

            $gradeRecords = $DB->get_records_sql($gradeSql, array(
                'courseid' => $courseId,
                'userid'   => $userId
            ));

            if (!empty($gradeRecords)) {
                foreach ($gradeRecords as $gr) {
                    if ($gr->itemtype === 'course') {
                        if ($gr->finalgrade !== null) {
                            $courseTotalGrade = round((float)$gr->finalgrade, 2);
                        } elseif ($gr->rawgrade !== null) {
                            $courseTotalGrade = round((float)$gr->rawgrade, 2);
                        }
                    } elseif ($gr->itemtype === 'category') {
                        $rawName = !empty($gr->itemname) ? (string)$gr->itemname : '';
                        $itemName = strtolower(trim($rawName));
                        $catGrade = null;
                        if ($gr->finalgrade !== null) {
                            $catGrade = round((float)$gr->finalgrade, 2);
                        } elseif ($gr->rawgrade !== null) {
                            $catGrade = round((float)$gr->rawgrade, 2);
                        }
                        if ($catGrade !== null && !empty($gr->grademax) && (float)$gr->grademax > 5.0) {
                            $catGrade = round(($catGrade / (float)$gr->grademax) * 5.0, 2);
                        }
                        if ($catGrade !== null) {
                            if (strpos($itemName, '1') !== false || strpos($itemName, 'primer') !== false) {
                                $nota1 = $catGrade;
                            } elseif (strpos($itemName, '2') !== false || strpos($itemName, 'segundo') !== false) {
                                $nota2 = $catGrade;
                            } elseif (strpos($itemName, '3') !== false || strpos($itemName, 'tercer') !== false) {
                                $nota3 = $catGrade;
                            } elseif (strpos($itemName, 'ex') !== false || strpos($itemName, 'final') !== false) {
                                $exa = $catGrade;
                            }
                        }
                    } elseif ($gr->itemtype === 'mod') {
                        $finalGrade = null;
                        $presento = false;
                        if ($gr->finalgrade !== null) {
                            $finalGrade = round((float)$gr->finalgrade, 2);
                            $presento = true;
                        } elseif ($gr->rawgrade !== null) {
                            $finalGrade = round((float)$gr->rawgrade, 2);
                            $presento = true;
                        }

                        $notaMax = ($gr->grademax !== null) ? round((float)$gr->grademax, 2) : 5.0;
                        $notaMin = ($gr->grademin !== null) ? round((float)$gr->grademin, 2) : 0.0;

                        if ($presento && $finalGrade !== null) {
                            $finalGradeNorm = ($notaMax > 0 && $notaMax != 5.0) ? round(($finalGrade / $notaMax) * 5.0, 2) : $finalGrade;
                            $totalNotaMods += $finalGradeNorm;
                            $countModsGraded++;
                        }

                        $actNameLow = strtolower(trim($gr->itemname ? (string)$gr->itemname : ''));
                        if (strpos($actNameLow, 'primer previo') !== false || strpos($actNameLow, '1er previo') !== false || strpos($actNameLow, '1p') !== false) {
                            if ($finalGrade !== null) { $nota1 = $finalGradeNorm; }
                        } elseif (strpos($actNameLow, 'segundo previo') !== false || strpos($actNameLow, '2do previo') !== false || strpos($actNameLow, '2p') !== false) {
                            if ($finalGrade !== null) { $nota2 = $finalGradeNorm; }
                        } elseif (strpos($actNameLow, 'tercer previo') !== false || strpos($actNameLow, '3er previo') !== false || strpos($actNameLow, '3p') !== false) {
                            if ($finalGrade !== null) { $nota3 = $finalGradeNorm; }
                        } elseif (strpos($actNameLow, 'examen final') !== false || strpos($actNameLow, 'evaluacion final') !== false) {
                            if ($finalGrade !== null) { $exa = $finalGradeNorm; }
                        }

                        $actividades[] = array(
                            'cmid'             => ($gr->cmid !== null) ? (int)$gr->cmid : 0,
                            'grade_item_id'    => (int)$gr->id,
                            'id_actividad'     => (int)$gr->iteminstance,
                            'tipo_actividad'   => $gr->itemmodule ? $gr->itemmodule : 'actividad',
                            'nombre_actividad' => $gr->itemname ? $gr->itemname : ($gr->itemmodule ? ucfirst($gr->itemmodule) : 'Actividad'),
                            'nota_final'       => ($finalGrade !== null) ? $finalGrade : 0.0,
                            'nota_maxima'      => $notaMax,
                            'nota_minima'      => $notaMin,
                            'presento'         => $presento,
                            'fecha_modificado' => $gr->timemodified
                        );
                    }
                }
            }
        } catch (Exception $e) {
            // Ignorar error al procesar calificaciones
        }

        if ($nota1 !== null && $nota1 > 5.0 && $nota1 <= 100.0) {
            $nota1 = round(($nota1 / 100.0) * 5.0, 1);
        }
        if ($nota2 !== null && $nota2 > 5.0 && $nota2 <= 100.0) {
            $nota2 = round(($nota2 / 100.0) * 5.0, 1);
        }
        if ($nota3 !== null && $nota3 > 5.0 && $nota3 <= 100.0) {
            $nota3 = round(($nota3 / 100.0) * 5.0, 1);
        }
        if ($exa !== null && $exa > 5.0 && $exa <= 100.0) {
            $exa = round(($exa / 100.0) * 5.0, 1);
        }

        $sumPart = 0.0;
        $cntPart = 0;
        if ($nota1 !== null) { $sumPart += $nota1; $cntPart++; }
        if ($nota2 !== null) { $sumPart += $nota2; $cntPart++; }
        if ($nota3 !== null) { $sumPart += $nota3; $cntPart++; }
        if ($exa   !== null) { $sumPart += $exa;   $cntPart++; }

        if ($cntPart > 0) {
            $definitiva = round($sumPart / $cntPart, 1);
        } elseif ($courseTotalGrade !== null) {
            $definitiva = $courseTotalGrade;
        } else {
            $definitiva = 0.0;
        }

        if ($definitiva !== null && $definitiva > 5.0 && $definitiva <= 100.0) {
            $definitiva = round(($definitiva / 100.0) * 5.0, 1);
        }
        $summaryText = !empty($c->summary) ? strip_tags((string)$c->summary) : '';

        $cursosData[] = array(
            'id'              => $courseId,
            'fullname'        => (string)$c->fullname,
            'shortname'       => (string)$c->shortname,
            'idnumber'        => !empty($c->idnumber) ? (string)$c->idnumber : '',
            'summary'         => $summaryText,
            'profesor_email'  => $profEmail,
            'profesor_nombre' => $profNombre,
            'nota1'           => ($nota1 !== null) ? $nota1 : 0.0,
            'tiene_nota1'     => ($nota1 !== null),
            'nota2'           => ($nota2 !== null) ? $nota2 : 0.0,
            'tiene_nota2'     => ($nota2 !== null),
            'nota3'           => ($nota3 !== null) ? $nota3 : 0.0,
            'tiene_nota3'     => ($nota3 !== null),
            'exa'             => ($exa !== null) ? $exa : 0.0,
            'tiene_exa'       => ($exa !== null),
            'def'             => $definitiva,
            'actividades'     => $actividades
        );
    }
}

echo json_encode(array(
    'exito' => true,
    'datos' => array(
        'estudiante' => array(
            'id'              => $userId,
            'codigo'          => !empty($user->idnumber) ? $user->idnumber : $user->username,
            'username'        => $user->username,
            'nombres'         => $user->firstname,
            'apellidos'       => $user->lastname,
            'nombre_completo' => trim($user->firstname . ' ' . $user->lastname),
            'email'           => $user->email
        ),
        'total_cursos' => count($cursosData),
        'cursos'       => $cursosData
    )
));
exit(0);

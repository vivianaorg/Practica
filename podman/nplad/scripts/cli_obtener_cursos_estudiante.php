<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

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

// Buscar estudiante por idnumber o username
$user = $DB->get_record_select('user', "(idnumber = :c1 OR username = :c2) AND deleted = 0", array('c1' => $codigo, 'c2' => $codigo));

if (!$user) {
    echo json_encode(array(
        'exito'   => false,
        'mensaje' => 'Estudiante con codigo ' . $codigo . ' no encontrado en Moodle'
    ));
    exit(0);
}

$userId = (int)$user->id;

// Consultar cursos en los que el estudiante esta matriculado (excluyendo el sitio id 1)
$sqlCourses = "SELECT DISTINCT 
                   c.id, 
                   c.fullname, 
                   c.shortname, 
                   c.idnumber, 
                   c.summary
               FROM {course} c
               WHERE c.id > 1 AND (
                   c.id IN (
                       SELECT e.courseid 
                       FROM {enrol} e 
                       JOIN {user_enrolments} ue ON ue.enrolid = e.id 
                       WHERE ue.userid = :u1
                   )
                   OR c.id IN (
                       SELECT ctx.instanceid 
                       FROM {context} ctx 
                       JOIN {role_assignments} ra ON ra.contextid = ctx.id 
                       WHERE ra.userid = :u2 AND ctx.contextlevel = 50
                   )
               )
               ORDER BY c.fullname ASC";

$courses = $DB->get_records_sql($sqlCourses, array('u1' => $userId, 'u2' => $userId));

$cursosData = array();

if (!empty($courses)) {
    foreach ($courses as $c) {
        $courseId = (int)$c->id;

        // Obtener profesor del curso si existe
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
        $profEmail = 'docente@ufps.edu.co';
        $profNombre = 'Docente Moodle';
        if (!empty($teachers)) {
            $t = reset($teachers);
            $profEmail = !empty($t->email) ? $t->email : 'docente@ufps.edu.co';
            $profNombre = trim($t->firstname . ' ' . $t->lastname);
        }

        // Obtener items de calificacion y notas del estudiante
        $gradeSql = "SELECT 
                        gi.id AS grade_item_id,
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

        $actividades = array();
        $totalNotaMods = 0.0;
        $countModsGraded = 0;
        $courseTotalGrade = null;

        $nota1 = null;
        $nota2 = null;
        $nota3 = null;
        $exa   = null;

        if (!empty($gradeRecords)) {
            foreach ($gradeRecords as $gr) {
                if ($gr->itemtype === 'course') {
                    if ($gr->finalgrade !== null) {
                        $courseTotalGrade = round((float)$gr->finalgrade, 2);
                    } elseif ($gr->rawgrade !== null) {
                        $courseTotalGrade = round((float)$gr->rawgrade, 2);
                    }
                } elseif ($gr->itemtype === 'category') {
                    $itemName = mb_strtolower(trim($gr->itemname));
                    $catGrade = null;
                    if ($gr->finalgrade !== null) {
                        $catGrade = round((float)$gr->finalgrade, 2);
                    } elseif ($gr->rawgrade !== null) {
                        $catGrade = round((float)$gr->rawgrade, 2);
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
                        $totalNotaMods += $finalGrade;
                        $countModsGraded++;
                    }

                    $actividades[] = array(
                        'cmid'             => ($gr->cmid !== null) ? (int)$gr->cmid : 0,
                        'grade_item_id'    => (int)$gr->grade_item_id,
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

        // Si no habia categoria explicita para nota1, pero hay actividades calificadas o nota total de curso
        if ($nota1 === null) {
            if ($countModsGraded > 0) {
                $nota1 = round($totalNotaMods / $countModsGraded, 2);
            } elseif ($courseTotalGrade !== null) {
                $nota1 = $courseTotalGrade;
            }
        }

        $definitiva = ($courseTotalGrade !== null) ? $courseTotalGrade : (($nota1 !== null) ? $nota1 : 0.0);

        $cursosData[] = array(
            'id'              => $courseId,
            'fullname'        => $c->fullname,
            'shortname'       => $c->shortname,
            'idnumber'        => $c->idnumber,
            'summary'         => strip_tags($c->summary),
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

<?php
define('CLI_SCRIPT', true);
error_reporting(0);
@ini_set('display_errors', '0');

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

\core\cron::setup_user();

list($options, $unrecognized) = cli_get_params(
    array(
        'codigo' => '',
        'curso'  => '',
        'nota1'  => null,
        'nota2'  => null,
        'nota3'  => null,
        'exa'    => null,
        'def'    => null,
        'help'   => false
    ),
    array(
        'c' => 'codigo',
        'k' => 'curso',
        'h' => 'help'
    )
);

if ($options['help'] || empty($options['codigo']) || empty($options['curso'])) {
    echo json_encode(array('exito' => false, 'mensaje' => 'Parametros requeridos incompletos'));
    exit(1);
}

$codigo = trim($options['codigo']);
$cursoRef = trim($options['curso']);

$user = $DB->get_record_select(
    'user',
    "(LOWER(idnumber) = LOWER(:c1) OR LOWER(username) = LOWER(:c2)) AND deleted = 0",
    array('c1' => $codigo, 'c2' => $codigo)
);

if (!$user) {
    echo json_encode(array('exito' => false, 'mensaje' => 'Estudiante no encontrado'));
    exit(0);
}

$course = null;
if (is_numeric($cursoRef)) {
    $course = $DB->get_record('course', array('id' => (int)$cursoRef));
}
if (!$course) {
    $course = $DB->get_record('course', array('shortname' => $cursoRef));
}
if (!$course) {
    $course = $DB->get_record_select('course', "LOWER(shortname) = LOWER(:sn) OR LOWER(idnumber) = LOWER(:idn)", array('sn' => $cursoRef, 'idn' => $cursoRef));
}

if (!$course) {
    echo json_encode(array('exito' => false, 'mensaje' => 'Curso no encontrado'));
    exit(0);
}

$courseId = (int)$course->id;
$userId   = (int)$user->id;

$gradeItems = $DB->get_records('grade_items', array('courseid' => $courseId));

$mapCortes = array(
    'nota1' => array('labels' => array('1', 'primer', 'corte 1', 'previo 1', 'corte1', 'primer corte'), 'val' => ($options['nota1'] !== null) ? round(floatval($options['nota1']), 2) : null),
    'nota2' => array('labels' => array('2', 'segundo', 'corte 2', 'previo 2', 'corte2', 'segundo corte'), 'val' => ($options['nota2'] !== null) ? round(floatval($options['nota2']), 2) : null),
    'nota3' => array('labels' => array('3', 'tercer', 'corte 3', 'previo 3', 'corte3', 'tercer corte'), 'val' => ($options['nota3'] !== null) ? round(floatval($options['nota3']), 2) : null),
    'exa'   => array('labels' => array('ex', 'final', 'examen final', 'examen'), 'val' => ($options['exa'] !== null) ? round(floatval($options['exa']), 2) : null),
);

$actualizados = 0;

foreach ($mapCortes as $corteKey => $corteCfg) {
    if ($corteCfg['val'] === null) {
        continue;
    }
    $targetItem = null;
    if (!empty($gradeItems)) {
        foreach ($gradeItems as $gi) {
            if ($gi->itemtype === 'course') {
                continue;
            }
            $iName = strtolower(trim((string)$gi->itemname));
            foreach ($corteCfg['labels'] as $lbl) {
                if (strpos($iName, $lbl) !== false) {
                    $targetItem = $gi;
                    break 2;
                }
            }
        }
    }

    if (!$targetItem) {
        $newItem = new stdClass();
        $newItem->courseid     = $courseId;
        $newItem->itemname     = ucfirst($corteKey);
        $newItem->itemtype     = 'manual';
        $newItem->gradetype    = 1;
        $newItem->grademax     = 5.0;
        $newItem->grademin     = 0.0;
        $newItem->timecreated  = time();
        $newItem->timemodified = time();
        $newId = $DB->insert_record('grade_items', $newItem);
        if ($newId) {
            $targetItem = $DB->get_record('grade_items', array('id' => $newId));
            $gradeItems[$newId] = $targetItem;
        }
    }

    if ($targetItem) {
        $gradeVal = $corteCfg['val'];
        $itemMax = (!empty($targetItem->grademax) && (float)$targetItem->grademax > 0) ? (float)$targetItem->grademax : 5.0;
        if (abs($itemMax - 5.0) > 0.01 && $itemMax > 0) {
            $gradeValInItem = round(($gradeVal / 5.0) * $itemMax, 2);
        } else {
            $gradeValInItem = $gradeVal;
        }

        $existing = $DB->get_record('grade_grades', array('itemid' => $targetItem->id, 'userid' => $userId));
        if ($existing) {
            $existing->rawgrade     = $gradeValInItem;
            $existing->finalgrade   = $gradeValInItem;
            $existing->timemodified = time();
            $DB->update_record('grade_grades', $existing);
        } else {
            $newGrade = new stdClass();
            $newGrade->itemid       = $targetItem->id;
            $newGrade->userid       = $userId;
            $newGrade->rawgrade     = $gradeValInItem;
            $newGrade->finalgrade   = $gradeValInItem;
            $newGrade->rawgrademax  = $itemMax;
            $newGrade->rawgrademin  = 0.0;
            $newGrade->timecreated  = time();
            $newGrade->timemodified = time();
            $DB->insert_record('grade_grades', $newGrade);
        }
        $actualizados++;
    }
}

if ($options['def'] !== null) {
    $defVal = round(floatval($options['def']), 2);
    $courseItem = null;
    if (!empty($gradeItems)) {
        foreach ($gradeItems as $gi) {
            if ($gi->itemtype === 'course') {
                $courseItem = $gi;
                break;
            }
        }
    }
    if ($courseItem) {
        $cMax = (!empty($courseItem->grademax) && (float)$courseItem->grademax > 0) ? (float)$courseItem->grademax : 100.0;
        $defValInItem = (abs($cMax - 5.0) > 0.01 && $cMax > 0) ? round(($defVal / 5.0) * $cMax, 2) : $defVal;
        $existingCourseGrade = $DB->get_record('grade_grades', array('itemid' => $courseItem->id, 'userid' => $userId));
        if ($existingCourseGrade) {
            $existingCourseGrade->rawgrade     = $defValInItem;
            $existingCourseGrade->finalgrade   = $defValInItem;
            $existingCourseGrade->timemodified = time();
            $DB->update_record('grade_grades', $existingCourseGrade);
        } else {
            $newCourseGrade = new stdClass();
            $newCourseGrade->itemid       = $courseItem->id;
            $newCourseGrade->userid       = $userId;
            $newCourseGrade->rawgrade     = $defValInItem;
            $newCourseGrade->finalgrade   = $defValInItem;
            $newCourseGrade->rawgrademax  = $cMax;
            $newCourseGrade->rawgrademin  = 0.0;
            $newCourseGrade->timecreated  = time();
            $newCourseGrade->timemodified = time();
            $DB->insert_record('grade_grades', $newCourseGrade);
        }
        $actualizados++;
    }
}

echo json_encode(array(
    'exito'        => true,
    'mensaje'      => 'Calificaciones sincronizadas en Moodle',
    'actualizados' => $actualizados,
    'curso_id'     => $courseId,
    'codigo'       => $codigo
));
exit(0);

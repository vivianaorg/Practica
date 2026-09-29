<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

\core\cron::setup_user();

list($options, $unrecognized) = cli_get_params(
    array(
        'course_id' => 0,
        'help'      => false
    ),
    array(
        'c' => 'course_id',
        'h' => 'help'
    )
);

if ($options['help'] || empty($options['course_id'])) {
    echo json_encode(array(
        'exito'   => false,
        'mensaje' => 'Parametro --course_id requerido'
    ));
    exit(1);
}

$courseId = (int)$options['course_id'];

$course = $DB->get_record('course', array('id' => $courseId));
if (!$course) {
    echo json_encode(array(
        'exito'   => false,
        'mensaje' => 'Curso no encontrado'
    ));
    exit(1);
}

$sql = "SELECT 
            gg.id AS grade_id,
            gi.id AS grade_item_id,
            gi.itemmodule,
            gi.iteminstance,
            gi.itemname,
            gi.grademax,
            gi.grademin,
            u.id AS user_id,
            u.username,
            u.idnumber,
            u.firstname,
            u.lastname,
            u.email,
            gg.finalgrade,
            gg.rawgrade,
            gg.timemodified
        FROM {grade_items} gi
        JOIN {grade_grades} gg ON gg.itemid = gi.id
        JOIN {user} u ON u.id = gg.userid
        WHERE gi.courseid = :courseid
          AND gi.itemtype = 'mod'
          AND u.deleted = 0
        ORDER BY u.lastname ASC, u.firstname ASC, gi.id ASC";

$records = $DB->get_records_sql($sql, array('courseid' => $courseId));

$estudiantesMap = array();

foreach ($records as $row) {
    $userId = (int)$row->user_id;

    if (!isset($estudiantesMap[$userId])) {
        $codigoEstudiante = !empty($row->idnumber) ? $row->idnumber : $row->username;
        $estudiantesMap[$userId] = array(
            'user_id'          => $userId,
            'codigo'           => $codigoEstudiante,
            'username'         => $row->username,
            'idnumber'         => $row->idnumber,
            'nombres'          => $row->firstname,
            'apellidos'        => $row->lastname,
            'nombre_completo'  => trim($row->firstname . ' ' . $row->lastname),
            'email'            => $row->email,
            'calificaciones'   => array()
        );
    }

    $notaFinal = ($row->finalgrade !== null) ? round((float)$row->finalgrade, 2) : 0.0;
    $notaMax   = ($row->grademax !== null) ? round((float)$row->grademax, 2) : 5.0;
    $notaMin   = ($row->grademin !== null) ? round((float)$row->grademin, 2) : 0.0;

    $estudiantesMap[$userId]['calificaciones'][] = array(
        'grade_item_id'    => (int)$row->grade_item_id,
        'id_actividad'     => (int)$row->iteminstance,
        'tipo_actividad'   => $row->itemmodule,
        'nombre_actividad' => $row->itemname ? $row->itemname : $row->itemmodule,
        'nota_final'       => $notaFinal,
        'nota_maxima'      => $notaMax,
        'nota_minima'      => $notaMin,
        'fecha_modificado' => $row->timemodified
    );
}

$resultadoFinal = array_values($estudiantesMap);

echo json_encode(array(
    'exito' => true,
    'datos' => array(
        'curso_id'          => $courseId,
        'curso_nombre'      => $course->fullname,
        'curso_codigo'      => $course->shortname,
        'total_estudiantes' => count($resultadoFinal),
        'estudiantes'       => $resultadoFinal
    )
));
exit(0);

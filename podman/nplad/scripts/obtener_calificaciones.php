<?php
header('Content-Type: application/json; charset=utf-8');

define('TOKEN_ESPERADO', 'a1c8f3e92b7d4560f8e1c2a9d6b3f704c5e8a1b9d2f6e3c7a0b4d8f1e5c9a2b6');

$token = isset($_GET['token']) ? $_GET['token'] : '';
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!hash_equals(TOKEN_ESPERADO, $token)) {
    http_response_code(401);
    echo json_encode(array(
        'exito'   => false,
        'datos'   => null,
        'mensaje' => 'Token de autenticacion invalido'
    ));
    exit;
}

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(array(
        'exito'   => false,
        'datos'   => null,
        'mensaje' => 'course_id invalido o faltante'
    ));
    exit;
}

$cmd = '/usr/local/bin/php /var/www/html/admin/cli/obtener_calificaciones.php --course_id=' . escapeshellarg($courseId);
$output = shell_exec($cmd);

if ($output === null) {
    http_response_code(500);
    echo json_encode(array(
        'exito'   => false,
        'datos'   => null,
        'mensaje' => 'Error al ejecutar el script CLI de Moodle'
    ));
    exit;
}

$decoded = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(array(
        'exito'   => false,
        'datos'   => null,
        'mensaje' => 'Respuesta no estructurada del script CLI',
        'raw'     => $output
    ));
    exit;
}

echo json_encode($decoded);

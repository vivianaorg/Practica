<?php
header('Content-Type: application/json; charset=utf-8');

define('TOKEN_ESPERADO', 'a1c8f3e92b7d4560f8e1c2a9d6b3f704c5e8a1b9d2f6e3c7a0b4d8f1e5c9a2b6');

$token  = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
$codigo = isset($_REQUEST['codigo']) ? trim($_REQUEST['codigo']) : '';
$curso  = isset($_REQUEST['curso']) ? trim($_REQUEST['curso']) : '';

if (!hash_equals(TOKEN_ESPERADO, $token)) {
    http_response_code(401);
    echo json_encode(array(
        'exito'   => false,
        'datos'   => null,
        'mensaje' => 'Token de autenticacion invalido'
    ));
    exit;
}

if (empty($codigo) || empty($curso)) {
    http_response_code(400);
    echo json_encode(array(
        'exito'   => false,
        'datos'   => null,
        'mensaje' => 'Parametros codigo y curso requeridos'
    ));
    exit;
}

$cmd = '/usr/local/bin/php /var/www/html/admin/cli/sincronizar_calificaciones.php'
     . ' --codigo=' . escapeshellarg($codigo)
     . ' --curso=' . escapeshellarg($curso);

if (isset($_REQUEST['nota1']) && $_REQUEST['nota1'] !== '') {
    $cmd .= ' --nota1=' . escapeshellarg($_REQUEST['nota1']);
}
if (isset($_REQUEST['nota2']) && $_REQUEST['nota2'] !== '') {
    $cmd .= ' --nota2=' . escapeshellarg($_REQUEST['nota2']);
}
if (isset($_REQUEST['nota3']) && $_REQUEST['nota3'] !== '') {
    $cmd .= ' --nota3=' . escapeshellarg($_REQUEST['nota3']);
}
if (isset($_REQUEST['exa']) && $_REQUEST['exa'] !== '') {
    $cmd .= ' --exa=' . escapeshellarg($_REQUEST['exa']);
}
if (isset($_REQUEST['def']) && $_REQUEST['def'] !== '') {
    $cmd .= ' --def=' . escapeshellarg($_REQUEST['def']);
}

$output = shell_exec($cmd);

if ($output === null) {
    http_response_code(500);
    echo json_encode(array(
        'exito'   => false,
        'datos'   => null,
        'mensaje' => 'Error al ejecutar script de sincronizacion en Moodle'
    ));
    exit;
}

$jsonStart = strpos($output, '{');
$jsonEnd   = strrpos($output, '}');
if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart) {
    $cleanJson = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
    $decoded = json_decode($cleanJson, true);
} else {
    $decoded = json_decode($output, true);
}

if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
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
exit;

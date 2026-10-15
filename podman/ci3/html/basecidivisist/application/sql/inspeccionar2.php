<?php
define('BASEPATH', true);

$configFile = __DIR__ . '/../config/database2.php';
require $configFile;
$dbConfig = $config['database2'];

$conn = @oci_pconnect(
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['hostname'],
    $dbConfig['char_set']
);

if (!$conn) {
    echo "Error conexion: " . json_encode(oci_error()) . "\n";
    exit(1);
}

echo "=== TABLAS EN SIA / MATRICULA / UDOCENTE ===\n";
$stid = oci_parse($conn, "SELECT owner, table_name FROM all_tables WHERE owner IN ('SIA', 'MATRICULA', 'UDOCENTE') ORDER BY owner, table_name");
@oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    echo $row['OWNER'] . "." . $row['TABLE_NAME'] . "\n";
}

echo "\n=== VISTAS EN SIA / MATRICULA / UDOCENTE ===\n";
$stid = oci_parse($conn, "SELECT owner, view_name FROM all_views WHERE owner IN ('SIA', 'MATRICULA', 'UDOCENTE') ORDER BY owner, view_name");
@oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    echo $row['OWNER'] . "." . $row['VIEW_NAME'] . "\n";
}

echo "\n=== OBJETOS QUE CONTIENEN 'INCLUSION' O 'MATRICULA' ===\n";
$stid = oci_parse($conn, "SELECT owner, object_name, object_type FROM all_objects WHERE UPPER(object_name) LIKE '%INCLUSION%' OR UPPER(object_name) LIKE '%MATRICULADA%'");
@oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    echo $row['OWNER'] . "." . $row['OBJECT_NAME'] . " (" . $row['OBJECT_TYPE'] . ")\n";
}

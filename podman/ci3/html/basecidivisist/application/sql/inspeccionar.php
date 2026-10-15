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

echo "=== 1. COLUMNAS DE SIA.D2_GRUPO_CARGADO ===\n";
$stid = oci_parse($conn, "SELECT column_name, data_type, data_length FROM all_tab_columns WHERE table_name = 'D2_GRUPO_CARGADO' ORDER BY column_id");
oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")\n";
}

echo "\n=== 2. EJEMPLOS DE FILAS EN SIA.D2_GRUPO_CARGADO (Top 3) ===\n";
$stid = oci_parse($conn, "SELECT * FROM SIA.D2_GRUPO_CARGADO WHERE ROWNUM <= 3");
@oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    print_r($row);
}

echo "\n=== 3. PROFESORES CON GRUPOS EN D2_GRUPO_CARGADO ===\n";
$stid = oci_parse($conn, "SELECT COD_PROFESOR, COUNT(*) as TOTAL FROM SIA.D2_GRUPO_CARGADO GROUP BY COD_PROFESOR");
@oci_execute($stid);
$c = 0;
while (($row = oci_fetch_assoc($stid)) != false && $c < 10) {
    print_r($row);
    $c++;
}

echo "\n=== 4. TABLAS ACCESIBLES QUE COINCIDEN CON 'GRUPO' O 'MATRICULA' ===\n";
$stid = oci_parse($conn, "SELECT owner, table_name FROM all_tables WHERE table_name LIKE '%GRUPO%' OR table_name LIKE '%MATRICULA%' ORDER BY owner, table_name");
oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    echo $row['OWNER'] . "." . $row['TABLE_NAME'] . "\n";
}

echo "\n=== 5. VISTAS ACCESIBLES QUE COINCIDEN CON 'GRUPO' O 'MATRICULA' ===\n";
$stid = oci_parse($conn, "SELECT owner, view_name FROM all_views WHERE view_name LIKE '%GRUPO%' OR view_name LIKE '%MATRICULA%' ORDER BY owner, view_name");
oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    echo $row['OWNER'] . "." . $row['VIEW_NAME'] . "\n";
}

echo "\n=== 6. TABLAS PROPIAS DE UDOCENTE ===\n";
$stid = oci_parse($conn, "SELECT table_name FROM user_tables ORDER BY table_name");
oci_execute($stid);
while (($row = oci_fetch_assoc($stid)) != false) {
    echo $row['TABLE_NAME'] . "\n";
}

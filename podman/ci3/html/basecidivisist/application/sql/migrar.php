<?php
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la terminal (CLI).\n");
}

define('BASEPATH', true);

$configFile = __DIR__ . '/../config/database2.php';
if (!file_exists($configFile)) {
    die("Error: No se encuentra el archivo de configuracion: $configFile\n");
}

require $configFile;

if (!isset($config['database2'])) {
    die("Error: Configuracion de database2 no definida en el archivo.\n");
}

$dbConfig = $config['database2'];

$conn = @oci_pconnect(
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['hostname'],
    $dbConfig['char_set']
);

if (!$conn) {
    $err = oci_error();
    die("Error de conexion a Oracle: " . (isset($err['message']) ? $err['message'] : 'Desconocido') . "\n");
}

echo "Conexion exitosa con Oracle (" . $dbConfig['username'] . ")\n";

$sentencias = array(
    "CREATE SEQUENCE SEQ_CONFIG_RUBRICA START WITH 1 INCREMENT BY 1 NOCACHE",
    "CREATE TABLE CONFIG_RUBRICA (
        ID                  NUMBER PRIMARY KEY,
        COD_PROFESOR        VARCHAR2(20) NOT NULL,
        COD_MATERIA         VARCHAR2(20) NOT NULL,
        GRUPO               VARCHAR2(5)  NOT NULL,
        SEMESTRE            VARCHAR2(10) NOT NULL,
        TIPO_PREVIO         VARCHAR2(20) NOT NULL,
        ID_ACTIVIDAD_MOODLE NUMBER NOT NULL,
        NOMBRE_ACTIVIDAD    VARCHAR2(200) NOT NULL,
        TIPO_ACTIVIDAD      VARCHAR2(50),
        PORCENTAJE          NUMBER(5,2) NOT NULL,
        FECHA_CREACION      DATE DEFAULT SYSDATE
    )",
    "CREATE INDEX IDX_RUBRICA_BUSQUEDA ON CONFIG_RUBRICA (COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO)",
    "CREATE SEQUENCE SEQ_SUBNOTAS START WITH 1 INCREMENT BY 1 NOCACHE",
    "CREATE TABLE SUBNOTAS (
        ID                  NUMBER PRIMARY KEY,
        COD_PROFESOR        VARCHAR2(20) NOT NULL,
        COD_MATERIA         VARCHAR2(20) NOT NULL,
        GRUPO               VARCHAR2(5)  NOT NULL,
        SEMESTRE            VARCHAR2(10) NOT NULL,
        COD_ESTUDIANTE      VARCHAR2(20) NOT NULL,
        TIPO_PREVIO         VARCHAR2(20) NOT NULL,
        ID_ACTIVIDAD_MOODLE NUMBER NOT NULL,
        NOMBRE_ACTIVIDAD    VARCHAR2(200) NOT NULL,
        NOTA_ORIGINAL       NUMBER(5,2) DEFAULT 0,
        NOTA_MAXIMA         NUMBER(5,2) DEFAULT 5,
        PORCENTAJE          NUMBER(5,2) NOT NULL,
        SUBNOTA             NUMBER(5,2) NOT NULL,
        ESTADO              VARCHAR2(20) DEFAULT 'SUGERIDA',
        FECHA_REGISTRO      DATE DEFAULT SYSDATE
    )",
    "CREATE INDEX IDX_SUBNOTAS_ESTUDIANTE ON SUBNOTAS (COD_ESTUDIANTE, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO)",
    "CREATE INDEX IDX_SUBNOTAS_GRUPO ON SUBNOTAS (COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO)"
);

foreach ($sentencias as $sql) {
    $stid = @oci_parse($conn, $sql);
    $ok = @oci_execute($stid);
    $resumen = substr(trim($sql), 0, 40) . '...';
    if ($ok) {
        echo "[OK]    $resumen\n";
    } else {
        $e = oci_error($stid);
        echo "[ERROR] $resumen -> " . (isset($e['message']) ? trim($e['message']) : 'Fallo') . "\n";
    }
}

echo "Proceso finalizado.\n";

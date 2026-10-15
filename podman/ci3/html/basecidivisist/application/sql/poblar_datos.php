<?php
/**
 * Script de automatización CLI para poblar datos de prueba en Oracle.
 * Compatible con PHP 5.6 y OCI8.
 *
 * Ejecución desde la consola en la máquina virtual o contenedor:
 *   php application/sql/poblar_datos.php
 * o mediante podman:
 *   podman exec -it ci3-dev php /var/www/html/basecidivisist/application/sql/poblar_datos.php
 */

define('BASEPATH', true);

$isCli = (php_sapi_name() === 'cli');

function imprimir($mensaje, $tipo = 'INFO') {
    global $isCli;
    $prefix = "[$tipo] ";
    if ($isCli) {
        echo $prefix . $mensaje . "\n";
    } else {
        $color = ($tipo === 'OK') ? 'green' : (($tipo === 'ERROR') ? 'red' : 'blue');
        echo "<div style='color:$color; font-family:monospace;'>$prefix" . htmlspecialchars($mensaje) . "</div>";
    }
}

$configFile = __DIR__ . '/../config/database2.php';
if (!file_exists($configFile)) {
    imprimir("No se encuentra el archivo de configuracion: $configFile", 'ERROR');
    exit(1);
}

require $configFile;

if (!isset($config['database2'])) {
    imprimir("Configuracion de database2 no definida en el archivo.", 'ERROR');
    exit(1);
}

$dbConfig = $config['database2'];

imprimir("Conectando a Oracle (" . $dbConfig['username'] . " en " . (is_array($dbConfig['hostname']) ? 'TNS' : 'Host') . ")...");

$conn = @oci_pconnect(
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['hostname'],
    $dbConfig['char_set']
);

if (!$conn) {
    $err = oci_error();
    imprimir("Error de conexion a Oracle: " . (isset($err['message']) ? $err['message'] : 'Desconocido'), 'ERROR');
    exit(1);
}

imprimir("Conexion exitosa con Oracle.", 'OK');

// Sentencias estructuradas para ejecutar en orden
$sentencias = array(
    // 1. Limpieza de pruebas previas
    "DELETE FROM MATRICULA.materiasMatriculadasPensum WHERE COD_CARRERA = '115' AND COD_ALUMNO = '1234' AND CODMAT = '5304'",
    "DELETE FROM SIA.D2_GRUPO_CARGADO WHERE COD_PROFESOR = '04608' AND COD_CARRERA = '115' AND COD_MATERIA = '5304' AND GRUPO = 'A'",
    "DELETE FROM SIA.GRUPOC WHERE COD_PROFESOR = '04608' AND COD_CARRERA = '115' AND COD_MATERIA = '5304' AND GRUPO = 'A'",
    "DELETE FROM SIA.GRUPO_INCLUSION WHERE COD_CARRERA = '115' AND COD_MATERIA = '5304' AND GRUPO = 'A' AND SECCIONAL = '01'",
    "DELETE FROM CONFIG_RUBRICA WHERE COD_PROFESOR = '04608' AND COD_MATERIA = '1155304' AND GRUPO = 'A' AND SEMESTRE = '2026-1'",
    "DELETE FROM SUBNOTAS WHERE COD_PROFESOR = '04608' AND COD_MATERIA = '1155304' AND GRUPO = 'A' AND SEMESTRE = '2026-1'",

    // 2. Estudiante: Materia matriculada
    "INSERT INTO MATRICULA.materiasMatriculadasPensum (
        COD_CARRERA, COD_ALUMNO, CODCARMAT, CODMAT, CODMATMAT, NOMBREMAT,
        GRUPO, CREDITOS, SEMESTRE, DEF, ID_MICRO, CONTENIDO, BIBLIOGRAFIA,
        EMAIL, SECCIONAL, RUBRICA,
        NOTA1, EST_NOTA1, NOTA2, EST_NOTA2, NOTA3, EST_NOTA3,
        EXA, EST_EXA, HAB, EST_HAB, OPC, EST_OPC
    ) VALUES (
        '115', '1234', '115', '5304', '5304', 'ANALISIS Y DISENO DE SISTEMAS',
        'A', 3, 6, 3.8, 101, 
        'Unidad 1: Modelado de Requisitos' || CHR(10) || 'Unidad 2: Diagramas UML y Patrones' || CHR(10) || 'Unidad 3: Arquitectura Hexagonal y Limpia',
        'Sommerville, Software Engineering 10th Ed.' || CHR(10) || 'Pressman, Ingenieria de Software 7ma Ed.',
        'profesor.sistemas@ufps.edu.co', '01', 1,
        4.0, 'V', 3.5, 'V', 0.0, 'S', 0.0, 'S', 0.0, 'S', 0.0, 'S'
    )",

    // 3. Docente: Grupo cargado presencial
    "INSERT INTO SIA.D2_GRUPO_CARGADO (
        COD_PROFESOR, CODIGO, COD_CARRERA, COD_MATERIA, GRUPO,
        NOMBRE, ROL_PROFESOR, NUM_ALUM_MATRICULADOS,
        SECCIONAL, NOMSECCIONAL, TIPO_CARRERA, UNICANOTA
    ) VALUES (
        '04608', '1155304-A', '115', '5304', 'A',
        'ANALISIS Y DISENO DE SISTEMAS', 'PRINCIPAL', 25,
        '01', 'CUCUTA', 'P', 'N'
    )",

    // 4. Docente: Titularidad de grupo
    "INSERT INTO SIA.GRUPOC (COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL, COD_PROFESOR)
     VALUES ('115', '5304', 'A', '01', '04608')",

    // 5. Inclusiones de notas (Cortes)
    "MERGE INTO SIA.TIPO_INCLUSION t
     USING (SELECT '1P' AS TIPO, '1P' AS TEXTO, 'PRIMER PREVIO' AS DESCRIPCION FROM DUAL) src
     ON (t.TIPO = src.TIPO)
     WHEN NOT MATCHED THEN INSERT (TIPO, TEXTO, DESCRIPCION) VALUES (src.TIPO, src.TEXTO, src.DESCRIPCION)",

    "MERGE INTO SIA.TIPO_INCLUSION t
     USING (SELECT '2P' AS TIPO, '2P' AS TEXTO, 'SEGUNDO PREVIO' AS DESCRIPCION FROM DUAL) src
     ON (t.TIPO = src.TIPO)
     WHEN NOT MATCHED THEN INSERT (TIPO, TEXTO, DESCRIPCION) VALUES (src.TIPO, src.TEXTO, src.DESCRIPCION)",

    "MERGE INTO SIA.TIPO_INCLUSION t
     USING (SELECT '3P' AS TIPO, '3P' AS TEXTO, 'TERCER PREVIO' AS DESCRIPCION FROM DUAL) src
     ON (t.TIPO = src.TIPO)
     WHEN NOT MATCHED THEN INSERT (TIPO, TEXTO, DESCRIPCION) VALUES (src.TIPO, src.TEXTO, src.DESCRIPCION)",

    // 6. Fechas de habilitación de cortes
    "INSERT INTO SIA.GRUPO_INCLUSION (
        TIPO, COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL,
        ORDEN, VALIDADO, EDITADO, FECHA_INI, FECHA_FIN, FECHA_VALIDA
    ) VALUES (
        '1P', '115', '5304', 'A', '01',
        1, 'N', 'N', SYSDATE - 5, SYSDATE + 10, SYSDATE + 10
    )",

    "INSERT INTO SIA.GRUPO_INCLUSION (
        TIPO, COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL,
        ORDEN, VALIDADO, EDITADO, FECHA_INI, FECHA_FIN, FECHA_VALIDA
    ) VALUES (
        '2P', '115', '5304', 'A', '01',
        2, 'N', 'N', SYSDATE - 2, SYSDATE + 15, SYSDATE + 15
    )",

    "INSERT INTO SIA.GRUPO_INCLUSION (
        TIPO, COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL,
        ORDEN, VALIDADO, EDITADO, FECHA_INI, FECHA_FIN, FECHA_VALIDA
    ) VALUES (
        '3P', '115', '5304', 'A', '01',
        3, 'S', 'N', SYSDATE - 25, SYSDATE - 5, SYSDATE - 5
    )",

    // 7. Rúbrica de Moodle (100%)
    "INSERT INTO CONFIG_RUBRICA (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
        '04608', '1155304', 'A', '2026-1', '1',
        101, 'Cuestionario Tema 1: Requerimientos', 'quiz', 40.0, SYSDATE
    )",

    "INSERT INTO CONFIG_RUBRICA (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
        '04608', '1155304', 'A', '2026-1', '1',
        102, 'Taller Grupal: Diagramas de Casos de Uso', 'assign', 60.0, SYSDATE
    )",

    // 8. Subnotas del estudiante
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1151234', '1',
        101, 'Cuestionario Tema 1: Requerimientos', 4.5, 5.0, 40.0, 1.80, 'ACEPTADA', SYSDATE
    )",

    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1151234', '1',
        102, 'Taller Grupal: Diagramas de Casos de Uso', 3.7, 5.0, 60.0, 2.22, 'ACEPTADA', SYSDATE
    )"
);

$errores = 0;
$exitos = 0;

foreach ($sentencias as $sql) {
    $stid = @oci_parse($conn, $sql);
    $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);
    $resumen = preg_replace('/\s+/', ' ', substr(trim($sql), 0, 55)) . '...';
    if ($ok) {
        imprimir("$resumen", 'OK');
        $exitos++;
    } else {
        $e = oci_error($stid);
        imprimir("$resumen -> " . (isset($e['message']) ? trim($e['message']) : 'Fallo'), 'ERROR');
        $errores++;
    }
}

if ($errores === 0) {
    oci_commit($conn);
    imprimir("Transaccion completada con exito (COMMIT). Total sentencias: $exitos", 'OK');
} else {
    oci_rollback($conn);
    imprimir("Se presentaron $errores errores. Se realizo ROLLBACK de los cambios.", 'ERROR');
}

echo "\nProceso finalizado.\n";

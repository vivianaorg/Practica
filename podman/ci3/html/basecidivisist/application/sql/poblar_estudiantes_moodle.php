<?php
/**
 * Script para registrar a los estudiantes de Moodle (1152001 y 1152002) en Oracle.
 * Compatible con PHP 5.6 y OCI8.
 *
 * Ejecución desde la consola en la VM:
 *   podman exec -it ci3-dev php /var/www/html/basecidivisist/application/sql/poblar_estudiantes_moodle.php
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

imprimir("Conectando a Oracle (" . $dbConfig['username'] . ")...");

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

// 1. Limpieza de subnotas anteriores de estos dos estudiantes
$limpieza = array(
    "DELETE FROM SUBNOTAS WHERE COD_ESTUDIANTE IN ('1152001', '1152002') AND COD_MATERIA IN ('1155304', 'biologia_111')",
    "DELETE FROM CONFIG_RUBRICA WHERE COD_PROFESOR = '04608' AND COD_MATERIA IN ('1155304', 'biologia_111') AND GRUPO IN ('A', '-') AND SEMESTRE = '2026-1'"
);

foreach ($limpieza as $sql) {
    $stid = @oci_parse($conn, $sql);
    @oci_execute($stid, OCI_NO_AUTO_COMMIT);
}

// 2. Insertar Configuración de Rúbrica en Oracle (Corte 1 y Corte 2)
$rubricas = array(
    // Corte 1 (1P) - Suma 100%
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

    // Corte 2 (2P) - Suma 100%
    "INSERT INTO CONFIG_RUBRICA (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
        '04608', '1155304', 'A', '2026-1', '2',
        201, 'Cuestionario Tema 2: Arquitectura y Patrones', 'quiz', 50.0, SYSDATE
    )",
    "INSERT INTO CONFIG_RUBRICA (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
        '04608', '1155304', 'A', '2026-1', '2',
        202, 'Laboratorio Práctico: Implementación Hexagonal', 'assign', 50.0, SYSDATE
    )",

    // Biología (biologia_111) - Corte 1 (1P)
    "INSERT INTO CONFIG_RUBRICA (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
        '04608', 'biologia_111', '-', '2026-1', '1',
        9, 'Tercer Previo', 'assign', 50.0, SYSDATE
    )",
    "INSERT INTO CONFIG_RUBRICA (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
        '04608', 'biologia_111', '-', '2026-1', '1',
        10, 'Taller General', 'assign', 50.0, SYSDATE
    )"
);

foreach ($rubricas as $sql) {
    $stid = @oci_parse($conn, $sql);
    $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);
    if ($ok) {
        imprimir("Rúbrica registrada correctamente", 'OK');
    } else {
        $e = oci_error($stid);
        imprimir("Fallo al registrar rúbrica: " . (isset($e['message']) ? $e['message'] : ''), 'ERROR');
    }
}

// 3. Insertar Subnotas para Estudiante 1 (1152001)
// 1P: 4.5*0.4 + 4.0*0.6 = 1.8 + 2.4 = 4.2
// 2P: 3.5*0.5 + 4.0*0.5 = 1.75 + 2.0 = 3.75
$subnotas1 = array(
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152001', '1',
        101, 'Cuestionario Tema 1: Requerimientos', 4.5, 5.0, 40.0, 1.80, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152001', '1',
        102, 'Taller Grupal: Diagramas de Casos de Uso', 4.0, 5.0, 60.0, 2.40, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152001', '2',
        201, 'Cuestionario Tema 2: Arquitectura y Patrones', 3.5, 5.0, 50.0, 1.75, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152001', '2',
        202, 'Laboratorio Práctico: Implementación Hexagonal', 4.0, 5.0, 50.0, 2.00, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', 'biologia_111', '-', '2026-1', '1152001', '1',
        9, 'Tercer Previo', 100.0, 100.0, 50.0, 2.50, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', 'biologia_111', '-', '2026-1', '1152001', '1',
        10, 'Taller General', 70.0, 100.0, 50.0, 1.75, 'ACEPTADA', SYSDATE
    )"
);

foreach ($subnotas1 as $sql) {
    $stid = @oci_parse($conn, $sql);
    $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);
    if ($ok) {
        imprimir("Subnota Estudiante 1152001 guardada", 'OK');
    }
}

// 4. Insertar Subnotas para Estudiante 2 (1152002)
// 1P: 3.8*0.4 + 3.2*0.6 = 1.52 + 1.92 = 3.44
// 2P: 4.0*0.5 + 3.6*0.5 = 2.00 + 1.80 = 3.80
$subnotas2 = array(
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152002', '1',
        101, 'Cuestionario Tema 1: Requerimientos', 3.8, 5.0, 40.0, 1.52, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152002', '1',
        102, 'Taller Grupal: Diagramas de Casos de Uso', 3.2, 5.0, 60.0, 1.92, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152002', '2',
        201, 'Cuestionario Tema 2: Arquitectura y Patrones', 4.0, 5.0, 50.0, 2.00, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', '1155304', 'A', '2026-1', '1152002', '2',
        202, 'Laboratorio Práctico: Implementación Hexagonal', 3.6, 5.0, 50.0, 1.80, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', 'biologia_111', '-', '2026-1', '1152002', '1',
        9, 'Tercer Previo', 80.0, 100.0, 50.0, 2.00, 'ACEPTADA', SYSDATE
    )",
    "INSERT INTO SUBNOTAS (
        ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
        ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
    ) VALUES (
        (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
        '04608', 'biologia_111', '-', '2026-1', '1152002', '1',
        10, 'Taller General', 70.0, 100.0, 50.0, 1.75, 'ACEPTADA', SYSDATE
    )"
);

foreach ($subnotas2 as $sql) {
    $stid = @oci_parse($conn, $sql);
    $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);
    if ($ok) {
        imprimir("Subnota Estudiante 1152002 guardada", 'OK');
    }
}

// 5. Intento opcional en MATRICULA.materiasMatriculadasPensum (si existe y tiene permisos)
$sqlMatricula = array(
    "DELETE FROM MATRICULA.materiasMatriculadasPensum WHERE COD_CARRERA = '115' AND COD_ALUMNO IN ('2001', '2002')",
    "INSERT INTO MATRICULA.materiasMatriculadasPensum (
        COD_CARRERA, COD_ALUMNO, CODCARMAT, CODMAT, CODMATMAT, NOMBREMAT,
        GRUPO, CREDITOS, SEMESTRE, DEF, ID_MICRO, EMAIL, SECCIONAL, RUBRICA,
        NOTA1, EST_NOTA1, NOTA2, EST_NOTA2, NOTA3, EST_NOTA3, EXA, EST_EXA, HAB, EST_HAB, OPC, EST_OPC
    ) VALUES (
        '115', '2001', '115', '5304', '5304', 'ANALISIS Y DISENO DE SISTEMAS',
        'A', 3, 6, 4.0, 101, 'profesor.sistemas@ufps.edu.co', '01', 1,
        4.2, 'V', 3.8, 'V', 0.0, 'S', 0.0, 'S', 0.0, 'S', 0.0, 'S'
    )",
    "INSERT INTO MATRICULA.materiasMatriculadasPensum (
        COD_CARRERA, COD_ALUMNO, CODCARMAT, CODMAT, CODMATMAT, NOMBREMAT,
        GRUPO, CREDITOS, SEMESTRE, DEF, ID_MICRO, EMAIL, SECCIONAL, RUBRICA,
        NOTA1, EST_NOTA1, NOTA2, EST_NOTA2, NOTA3, EST_NOTA3, EXA, EST_EXA, HAB, EST_HAB, OPC, EST_OPC
    ) VALUES (
        '115', '2002', '115', '5304', '5304', 'ANALISIS Y DISENO DE SISTEMAS',
        'A', 3, 6, 3.6, 101, 'profesor.sistemas@ufps.edu.co', '01', 1,
        3.4, 'V', 3.8, 'V', 0.0, 'S', 0.0, 'S', 0.0, 'S', 0.0, 'S'
    )"
);

foreach ($sqlMatricula as $sql) {
    $stid = @oci_parse($conn, $sql);
    $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);
    if ($ok) {
        imprimir("Registro insertado en MATRICULA.materiasMatriculadasPensum", 'OK');
    } else {
        imprimir("Esquema MATRICULA no accesible directamente (se usará integración SUBNOTAS)", 'INFO');
    }
}

// 6. Confirmar cambios
oci_commit($conn);
imprimir("Poblado completado con éxito en Oracle.", 'OK');

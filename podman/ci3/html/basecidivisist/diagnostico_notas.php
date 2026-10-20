<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '1151234';

$dsn      = 'oci:dbname=//localhost:1521/xe';
$user_ora = 'divisist';
$pass_ora = 'divisist';

function ora_query($dsn, $user, $pass, $sql) {
    try {
        $conn = new PDO($dsn, $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return array('ERROR' => $e->getMessage());
    }
}

function print_table($rows, $title) {
    echo "<h3 style='color:#c0392b;margin-top:20px'>$title</h3>";
    if (empty($rows)) {
        echo "<p style='color:#888'><em>Sin resultados</em></p>";
        return;
    }
    if (isset($rows['ERROR'])) {
        echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($rows['ERROR']) . "</p>";
        return;
    }
    echo "<div style='overflow-x:auto'><table border='1' cellpadding='4' cellspacing='0' style='border-collapse:collapse;font-size:12px;font-family:monospace'>";
    echo "<tr style='background:#2c3e50;color:#fff'>";
    foreach (array_keys($rows[0]) as $col) {
        echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr>";
    $i = 0;
    foreach ($rows as $row) {
        echo "<tr style='background:" . ($i % 2 === 0 ? '#f9f9f9' : '#fff') . "'>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars((string)$val) . "</td>";
        }
        echo "</tr>";
        $i++;
    }
    echo "</table></div>";
    echo "<p style='color:#555;font-size:11px'>" . count($rows) . " fila(s)</p>";
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>
<title>Diagnóstico Notas Estudiante</title>
<style>body{font-family:Arial,sans-serif;padding:20px;background:#f4f4f4}
form{background:#fff;padding:15px;border-radius:6px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
input[type=text]{padding:6px 10px;font-size:14px;border:1px solid #ccc;border-radius:4px;width:200px}
input[type=submit]{padding:6px 16px;background:#2c3e50;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px}
h2{color:#2c3e50} h3{margin-bottom:4px}
.bloque{background:#fff;padding:15px;margin-bottom:15px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
</style></head><body>";

echo "<h2>🔍 Diagnóstico de Notas — Estudiante</h2>";
echo "<form method='GET'>";
echo "Código estudiante: <input type='text' name='codigo' value='" . htmlspecialchars($codigo) . "'> ";
echo "<input type='submit' value='Diagnosticar'>";
echo "</form>";

$codCarrera = strlen($codigo) >= 3 ? substr($codigo, 0, 3) : '115';
$codAlumno  = strlen($codigo) > 3  ? substr($codigo, 3)    : $codigo;

echo "<div class='bloque'>";
echo "<strong>Código:</strong> $codigo &nbsp;|&nbsp; <strong>Carrera extraída:</strong> $codCarrera &nbsp;|&nbsp; <strong>Alumno extraído:</strong> $codAlumno";
echo "</div>";

echo "<div class='bloque'>";
$sql1 = "SELECT * FROM matricula.materiasMatriculadasPensum WHERE COD_ALUMNO=SUBSTR('$codigo','4','4') AND COD_CARRERA=SUBSTR('$codigo','1','3')";
echo "<p style='font-size:11px;color:#555'>SQL: <code>" . htmlspecialchars($sql1) . "</code></p>";
$rows1 = ora_query($dsn, $user_ora, $pass_ora, $sql1);
print_table($rows1, '1. Vista Oracle: matricula.materiasMatriculadasPensum');
echo "</div>";

echo "<div class='bloque'>";
$sql2 = "SELECT COD_ESTUDIANTE, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, ROUND(SUM(SUBNOTA),2) AS NOTA_TOTAL, MAX(ESTADO) AS ESTADO, COUNT(*) AS FILAS FROM SUBNOTAS WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ('" . strtolower($codigo) . "', '" . strtoupper($codigo) . "') GROUP BY COD_ESTUDIANTE, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO ORDER BY COD_MATERIA, TIPO_PREVIO";
$rows2 = ora_query($dsn, $user_ora, $pass_ora, $sql2);
print_table($rows2, '2. SUBNOTAS en Oracle para este estudiante (agrupadas por corte)');
echo "</div>";

echo "<div class='bloque'>";
$sql3 = "SELECT DISTINCT COD_MATERIA, GRUPO, SEMESTRE FROM SUBNOTAS ORDER BY COD_MATERIA";
$rows3 = ora_query($dsn, $user_ora, $pass_ora, $sql3);
print_table($rows3, '3. Todos los COD_MATERIA distintos en SUBNOTAS (ver formato)');
echo "</div>";

echo "<div class='bloque'>";
$sql4 = "SELECT COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, COD_ESTUDIANTE, ROUND(SUM(SUBNOTA),2) AS NOTA FROM SUBNOTAS GROUP BY COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, COD_ESTUDIANTE ORDER BY COD_MATERIA, TIPO_PREVIO, COD_ESTUDIANTE";
$rows4 = ora_query($dsn, $user_ora, $pass_ora, $sql4);
print_table($rows4, '4. Todas las calificaciones en SUBNOTAS (todos los estudiantes)');
echo "</div>";

echo "<div class='bloque'>";
$sql5 = "SELECT DISTINCT COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, COD_ESTUDIANTE FROM CONFIG_RUBRICA ORDER BY COD_MATERIA";
$rows5 = ora_query($dsn, $user_ora, $pass_ora, $sql5);
print_table($rows5, '5. CONFIG_RUBRICA — cursos con rúbrica configurada');
echo "</div>";

echo "<div class='bloque'>";
$moodle_api = 'http://localhost:10000';
$token_file = __DIR__ . '/application/config/moodle_token.php';
$moodle_token = 'TU_TOKEN';
if (file_exists($token_file)) {
    include $token_file;
}
$url = $moodle_api . "/local/divisist/api.php?action=cursos_estudiante&codigo=" . urlencode($codigo);
$ctx = stream_context_create(array('http' => array('timeout' => 5)));
$resp = @file_get_contents($url, false, $ctx);
echo "<h3 style='color:#c0392b'>6. Respuesta API Moodle — cursos_estudiante</h3>";
echo "<p style='font-size:11px;color:#555'>URL: <code>" . htmlspecialchars($url) . "</code></p>";
if ($resp === false) {
    echo "<p style='color:orange'><em>No se pudo conectar a Moodle API en $moodle_api</em></p>";
} else {
    $decoded = json_decode($resp, true);
    if ($decoded) {
        echo "<pre style='background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:4px;overflow:auto;font-size:11px'>";
        echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "</pre>";
    } else {
        echo "<pre style='background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:4px;overflow:auto;font-size:11px'>";
        echo htmlspecialchars($resp);
        echo "</pre>";
    }
}
echo "</div>";

echo "</body></html>";

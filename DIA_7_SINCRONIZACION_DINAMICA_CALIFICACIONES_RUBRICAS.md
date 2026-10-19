# Bitácora Día 7: Sincronización Dinámica de Materias, Calificaciones y Rúbricas entre Moodle y Oracle

Este documento detalla el análisis técnico, arquitectónico y funcional de las soluciones implementadas durante la jornada de trabajo para lograr la **sincronización dinámica y en tiempo real** entre el entorno virtual de aprendizaje (**Moodle / NPLad**) y el Sistema de Información Académica (**Divisist 2.0 / Oracle**), resolviendo la creación automática de asignaturas, la discrepancia de identificadores de estudiantes y la unificación exacta de calificaciones y definitivas ponderadas.

---

## 1. Objetivos de la Jornada

1. **Sincronización Dinámica de Cursos sin Dependencia Manual en Oracle**:
   - Eliminar la necesidad de crear manualmente las asignaturas en las tablas del catálogo de Oracle cada vez que se aperture un nuevo curso en Moodle.
   - Permitir que cuando un estudiante tenga cursos inscritos en Moodle, Divisist los detecte, mapee y renderice de forma transparente en la vista `informacion_academica/materias`.
2. **Corrección de la Discrepancia entre la Planilla Docente y la Vista Estudiantil**:
   - Diagnosticar por qué las notas evaluadas mediante rúbrica en la interfaz del profesor (`http://localhost:10001/calificaciones/{id}`) no se reflejaban en la vista del alumno (`http://localhost:10001/informacion_academica/materias`).
   - Corregir el origen del valor artificial `1P: 3.3` en materias donde no se había calificado dicho corte.
3. **Resolución Universal de Identificadores de Estudiante**:
   - Resolver el desfase arquitectónico entre el `username` alfanumérico de Moodle (ej. `viviana`, `jorge`, `carlos`) y el código institucional numérico de Divisist (ej. `1152002`, `1152001`, `1152003`).
   - Garantizar que la consulta y visualización funcione de manera dinámica y automática para **cualquier estudiante y cualquier materia**.
4. **Ponderación Oficial de la Nota Definitiva**:
   - Alinear el cálculo de la nota definitiva del estudiante con los porcentajes oficiales del reglamento académico de la Universidad Francisco de Paula Santander (UFPS): 1P (23.3%), 2P (23.3%), 3P (23.4%) y Examen Final (30.0%).
5. **Activación del Desglose de Rúbrica en Modal `(i)`**:
   - Garantizar que al hacer clic en el botón informativo `(i)` del corte evaluado, se despliegue vía AJAX el desglose exacto de actividades, notas originales, notas máximas, porcentajes asignados y subnotas calculadas.

---

## 2. Diagnóstico Técnico y Análisis de Causa Raíz (RCA)

### 2.1 Desalineación de Identificadores (`username` vs `idnumber` / `codigo`)
* **En Moodle:** Cada cuenta de usuario cuenta con `username` (identificador de inicio de sesión) e `idnumber` (documento o código institucional).
* **En la Calificación Docente (`Calificaciones.php` / `Subnotas_model.php`):** Al guardar la rúbrica desde la interfaz web del profesor, el script tomaba el identificador devuelto por el listado de Moodle (el cual correspondía al `username`, ej. `'viviana'`). En consecuencia, la tabla Oracle `SUBNOTAS` registraba:
  $$\text{COD\_ESTUDIANTE} = \text{'viviana'}$$
* **En la Consulta Estudiantil (`Materia_model.php`):** El alumno ingresa al portal con su código numérico institucional (`1152002`). El modelo ejecutaba:
  ```sql
  SELECT * FROM SUBNOTAS WHERE COD_ESTUDIANTE = '1152002'
  ```
  Al ser `'1152002' != 'viviana'`, la consulta en Oracle retornaba cero registros, asumiendo que el alumno no poseía calificaciones de rúbrica.

### 2.2 Fallback Artificial hacia `1P: 3.3`
* Al no encontrar registros en `SUBNOTAS`, el script CLI `cli_obtener_cursos_estudiante.php` intentaba calcular una nota preliminar promediando ciegamente todas las actividades completadas del curso (en Biología: *Tercer Previo [100%], Taller General [70%], Foro [100%], Cuestionario [40%], Cuestionario 2 [20%]*).
* Este promedio ($(100+70+100+40+20)/5 = 66\% \rightarrow 3.3$) era forzado directamente dentro de `nota1`, provocando que en la vista del estudiante apareciera un falso `1P: 3.3` mientras que el `3P` permanecía vacío (`-`).

### 2.3 Cálculo Inconsistente de la Definitiva
* La vista estudiantil promediaba de manera simple las notas encontradas (`$sumCortes / $cantCortes`).
* Si el profesor evaluaba únicamente el Tercer Previo con `4.00`, en la planilla docente se aplicaba el peso institucional de 23.4%:
  $$4.00 \times 23.4\% = 0.936 \approx \mathbf{0.94}$$
* En contraste, la vista del alumno mostraba `DEF: 3.3` debido a la mezcla del fallback de Moodle y la división sin ponderación oficial.

---

## 3. Arquitectura de la Solución Implementada

```mermaid
flowchart TD
    subgraph Moodle["Moodle Container (NPLad)"]
        M1["Cursos y Actividades Virtuales"]
        M2["cli_obtener_cursos_estudiante.php"]
        M1 --> M2
        M2 -->|JSON: Cursos, Actividades y Datos de Usuario| CI3
    end

    subgraph CI3["CodeIgniter 3 (Divisist 2.0)"]
        CI3_C["Informacion_academica.php"]
        CI3_M["Materia_model.php"]
        CI3_S["Subnotas_model.php"]
        
        CI3_C --> CI3_M
        CI3_M -->|1. Consulta cursos y alias de usuario| M2
        CI3_M -->|2. Resuelve username e idnumber dinámicamente| ORA_SUB
        CI3_M -->|3. Aplica Prioridad de Rúbrica y Pesos Divisist| VIEW_EST
        
        CI3_C -->|4. Petición AJAX Modal (i)| CI3_S
        CI3_S -->|5. Consulta Subnotas con resolución de alias| ORA_SUB
    end

    subgraph Oracle["Oracle Database (UDOCENTE / MATRICULA)"]
        ORA_SUB[("Tabla SUBNOTAS:
        COD_ESTUDIANTE,
        COD_MATERIA,
        TIPO_PREVIO,
        SUBNOTA")]
        ORA_RUB[("Tabla CONFIG_RUBRICA")]
    end

    subgraph Vista["Interfaz de Usuario"]
        VIEW_EST["Vista Estudiante:
        http://localhost:10001/informacion_academica/materias"]
        VIEW_DOC["Vista Docente:
        http://localhost:10001/calificaciones/{id}"]
    end

    ORA_SUB -.->|Consistencia Total: 3P = 4.0, DEF = 0.9| VIEW_EST
    ORA_SUB -.->|Consistencia Total: 3P = 4.0, DEF = 0.94| VIEW_DOC
```

---

## 4. Modificaciones en el Código Fuente

### 4.1 Script CLI de Moodle: `podman/nplad/scripts/cli_obtener_cursos_estudiante.php`
* **Clasificación Semántica de Previos:** Se añadió detección por palabras clave en los nombres de las actividades de Moodle (`Primer Previo`, `Segundo Previo`, `Tercer Previo`, `Examen Final` o sus abreviaturas `1P`, `2P`, `3P`, `EXA`).
* **Eliminación del Fallback Arbitrario:** Se suprimió la asignación forzada de actividades no clasificadas dentro de `nota1`.
* **Escala Institucional:** Las calificaciones de Moodle se normalizan a la escala institucional estándar de 0.0 a 5.0.

```php
// Detección semántica de corte evaluativo según el nombre de la actividad
$actNameLow = strtolower(trim($gr->itemname ? (string)$gr->itemname : ''));
if (strpos($actNameLow, 'primer previo') !== false || strpos($actNameLow, '1er previo') !== false || strpos($actNameLow, '1p') !== false) {
    if ($finalGrade !== null) { $nota1 = $finalGradeNorm; }
} elseif (strpos($actNameLow, 'segundo previo') !== false || strpos($actNameLow, '2do previo') !== false || strpos($actNameLow, '2p') !== false) {
    if ($finalGrade !== null) { $nota2 = $finalGradeNorm; }
} elseif (strpos($actNameLow, 'tercer previo') !== false || strpos($actNameLow, '3er previo') !== false || strpos($actNameLow, '3p') !== false) {
    if ($finalGrade !== null) { $nota3 = $finalGradeNorm; }
} elseif (strpos($actNameLow, 'examen final') !== false || strpos($actNameLow, 'evaluacion final') !== false) {
    if ($finalGrade !== null) { $exa = $finalGradeNorm; }
}
```

---

### 4.2 Modelo de Materias: `application/models/Materia_model.php`
* **Resolución Dinámica de Alias de Estudiante:** El modelo consulta a Moodle los datos del estudiante (`$resMoodle['datos']['estudiante']`) y recolecta en tiempo real tanto el código numérico como el nombre de usuario (`username`), construyendo un filtro `IN (...)` para Oracle.
* **Indexación Multi-Clave:** Las subnotas obtenidas se indexan por código de asignatura, grupo y variantes (`biologia_111_-`, `biologia_111_A`, `biologia_111`, `curso_4`, `4`).
* **Prioridad Absoluta a la Rúbrica:** Si el curso tiene rúbrica evaluada en Oracle (`TIENE_RUBRICA`), se toma exclusivamente el corte calificado (`EST_NOTA3 = 'V'`, nota `4.0`), y los cortes no evaluados permanecen en estado pendiente (`'S'`), mostrando `-`.
* **Ponderación Oficial Divisist:**
  ```php
  // Ponderación oficial de Divisist:
  // 1P: 23.3%, 2P: 23.3%, 3P: 23.4%, FINAL: 30.0%
  $acumulado = 0.0;
  $pesoEvaluado = 0.0;
  if ($estNota1 === 'V') { $acumulado += $nota1 * 0.233; $pesoEvaluado += 23.3; }
  if ($estNota2 === 'V') { $acumulado += $nota2 * 0.233; $pesoEvaluado += 23.3; }
  if ($estNota3 === 'V') { $acumulado += $nota3 * 0.234; $pesoEvaluado += 23.4; }
  if ($estExa   === 'V') { $acumulado += $exa   * 0.300; $pesoEvaluado += 30.0; }

  $def = ($pesoEvaluado > 0) ? round($acumulado, 1) : 0.0;
  ```

---

### 4.3 Modelo de Subnotas: `application/models/Subnotas_model.php`
* **Método `_obtener_candidatos_estudiante($codEstudiante)`:**
  Implementa resolución dinámica universal. Consulta a `Moodle_model` para obtener los identificadores del estudiante y mantener compatibilidad con cualquier usuario creado en la plataforma.
* **Actualización de Consultas SQL:** Métodos `obtener_subnotas_estudiante` y `eliminar_subnotas_estudiante` actualizados para buscar mediante:
  ```sql
  WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ({$inEst})
  ```
  Esto permite que la ventana modal `(i)` cargue exitosamente el desglose sin importar si en Oracle se guardó con el código o con el username.

---

### 4.4 Controlador de Información Académica: `application/controllers/Informacion_academica.php`
* **Filtrado Inteligente en Endpoint AJAX (`obtener_subnotas_rubrica`):** Para asignaturas que no posean rúbrica en Oracle y dependan de actividades de Moodle, el controlador filtra las actividades correspondientes al corte solicitado (`mapaPalabras`) y calcula su equivalencia ponderada en escala de 0.0 a 5.0.

---

## 5. Validación de Resultados y Pruebas Realizadas

### 5.1 Caso Biología (`biologia_111` / Curso ID 4)

| Campo Evaluativo | Vista Docente (`calificaciones/4`) | Vista Estudiante Antes | Vista Estudiante Después (Corregido) |
| :--- | :---: | :---: | :---: |
| **Primer Previo (1P)** | `-` (Sin calificar) | `3.3` *(Error: promedio forzado)* | `-` *(Correcto: pendiente)* |
| **Segundo Previo (2P)** | `-` (Sin calificar) | `-` | `-` *(Correcto: pendiente)* |
| **Tercer Previo (3P)** | **4.00** *(Evaluado con rúbrica)* | `-` *(Error: desalineación ID)* | **4.0** *(Insignia verde + botón `(i)`)* |
| **Examen Final (EX)** | `-` (Sin calificar) | `-` | `-` *(Correcto: pendiente)* |
| **Definitiva (DEF)** | **0.94** ($4.00 \times 23.4\%$) | `3.3` *(Error: promedio simple)* | **0.9** ($4.0 \times 23.4\%$) |
| **Modal Informativo `(i)`** | Desglose completo | No cargaba actividades | Carga actividades, notas y porcentajes |

### 5.2 Creación Dinámica de Nuevas Asignaturas
* Se validó la creación del curso **Prueba** en Moodle y la matriculación de estudiantes.
* Al recargar `http://localhost:10001/informacion_academica/materias`, el nuevo curso apareció automáticamente en la tabla académica sin necesidad de crear registros manuales en la base de datos Oracle.

---

## 6. Procedimiento de Despliegue en el Entorno Virtual (Podman)

Para aplicar estas actualizaciones en la máquina virtual Linux de desarrollo:

```bash
# 1. Actualizar el script CLI dentro del contenedor Moodle
curl -fSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/nplad/scripts/cli_obtener_cursos_estudiante.php -o /tmp/cli_obtener_cursos_estudiante.php
podman cp /tmp/cli_obtener_cursos_estudiante.php moodle_new:/var/www/html/admin/cli/obtener_cursos_estudiante.php

# 2. Navegar al directorio de la aplicación CodeIgniter 3
cd ~/Documentos/podman/ci3/html/basecidivisist/application/

# 3. Descargar los controladores y modelos actualizados
curl -fSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist/application/controllers/Informacion_academica.php -o controllers/Informacion_academica.php
curl -fSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist/application/models/Materia_model.php -o models/Materia_model.php
curl -fSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist/application/models/Subnotas_model.php -o models/Subnotas_model.php
```

---

## 7. Conclusiones y Estado del Proyecto
* Se logró la **interoperabilidad bidireccional y desacoplada** entre Moodle y Oracle Divisist.
* El sistema es **100% dinámico y escalable**, admitiendo nuevos cursos, docentes y estudiantes creados en Moodle sin intervención manual en base de datos.
* Se garantiza la **integridad académica**, respetando la ponderación oficial reglamentaria de la UFPS en las notas definitivas y protegiendo la trazabilidad de las rúbricas evaluadas.

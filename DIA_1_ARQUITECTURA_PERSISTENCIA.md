# Bitácora Día 1: Arquitectura y Persistencia de Rúbricas y Subnotas

Este documento resume las actividades técnicas realizadas durante la jornada del Día 1 de la práctica, orientadas a estructurar la base de datos y la capa de persistencia en Divisist (CodeIgniter 3 / PHP 5.6) para la integración de rúbricas y calificaciones desde Moodle (NPLad).

---

## 1. Contexto y Requerimientos del Módulo

El objetivo general es permitir que un docente en Divisist:
1. Seleccione actividades evaluativas de Moodle (cuestionarios, tareas, foros).
2. Asigne porcentajes a cada actividad dentro de un corte o previo (Primer Previo, Segundo Previo, etc.).
3. Calcule las notas de los estudiantes a partir de las calificaciones obtenidas en Moodle.
4. Tenga una nota sugerida opcional que pueda aceptar o modificar.
5. Permita al estudiante consultar el origen exacto de su nota mediante un botón "Ver más" que despliegue la rúbrica detallada.

---

## 2. Lo que se Realizó

### 2.1 Diseño del Esquema de Base de Datos
Se diseñó un modelo relacional de dos tablas principales para desacoplar la configuración de la rúbrica del registro histórico de notas de los estudiantes:

* **Archivo generado:** `application/sql/crear_tablas_rubricas_subnotas.sql`

#### A. Tabla `CONFIG_RUBRICA`
Almacena la regla o plantilla que define el docente para evaluar un previo:
* `ID`: Identificador autoincremental de la regla.
* `COD_PROFESOR`: Identificador del docente.
* `COD_MATERIA`: Código institucional de la asignatura.
* `GRUPO`: Grupo académico.
* `SEMESTRE`: Periodo lectivo (ej. 2026-1).
* `TIPO_PREVIO`: Corte ('1', '2', '3', 'FINAL').
* `ID_ACTIVIDAD_MOODLE`: ID del módulo/actividad dentro de Moodle.
* `NOMBRE_ACTIVIDAD`: Nombre del cuestionario o tarea.
* `TIPO_ACTIVIDAD`: Tipo de módulo (quiz, assign, etc.).
* `PORCENTAJE`: Peso porcentual asignado a la actividad.
* `FECHA_CREACION`: Marca temporal de registro.
* **Índice:** `IDX_RUBRICA_BUSQUEDA` para optimizar consultas por profesor, materia, grupo y previo.

#### B. Tabla `SUBNOTAS`
Almacena la calificación individualizada por estudiante para cada actividad de la rúbrica:
* `ID`: Identificador único del registro de nota.
* `COD_PROFESOR`, `COD_MATERIA`, `GRUPO`, `SEMESTRE`, `TIPO_PREVIO`: Contexto académico.
* `COD_ESTUDIANTE`: Código institucional del alumno.
* `ID_ACTIVIDAD_MOODLE`: Actividad evaluada.
* `NOMBRE_ACTIVIDAD`: Nombre de la actividad evaluada.
* `NOTA_ORIGINAL`: Calificación directa registrada en Moodle.
* `NOTA_MAXIMA`: Escala máxima de la actividad (5.0 o 100).
* `PORCENTAJE`: Porcentaje ponderado aplicado.
* `SUBNOTA`: Puntos que aporta la actividad a la nota definitiva.
* `ESTADO`: Estado de la calificación ('SUGERIDA', 'ACEPTADA', 'MODIFICADA').
* `FECHA_REGISTRO`: Marca temporal del cálculo.
* **Índices:** `IDX_SUBNOTAS_ESTUDIANTE` (para la consulta del botón "Ver más") e `IDX_SUBNOTAS_GRUPO` (para la planilla consolidada del docente).

---

### 2.2 Creación del Modelo `Subnotas_model.php`
Se construyó el modelo en CodeIgniter 3 con compatibilidad estricta con **PHP 5.6** y uso de la librería de conexión OCI8 del sistema (`Database2`):

* **Archivo generado:** `application/models/Subnotas_model.php`

**Métodos implementados:**
* `guardar_rubrica(...)`: Limpia configuraciones anteriores del corte e inserta las nuevas actividades con sus respectivos porcentajes.
* `obtener_rubrica(...)`: Recupera la rúbrica activa de un curso y previo.
* `eliminar_rubrica(...)`: Permite reiniciar la configuración de un corte.
* `guardar_subnotas_estudiante(...)`: Registra el cálculo detallado de notas por estudiante.
* `obtener_subnotas_estudiante(...)`: Consulta las subnotas de un estudiante en particular para alimentar la ventana modal del botón "Ver más".
* `eliminar_subnotas_estudiante(...)`: Limpia cálculos anteriores de un alumno antes de recalcular.
* `obtener_resumen_grupo(...)`: Realiza la sumatoria de subnotas (`SUM(SUBNOTA)`) agrupada por estudiante para presentarle al docente la **Nota Sugerida** del previo.
* `actualizar_estado_grupo(...)`: Cambia el estado a 'ACEPTADA' cuando el docente confirma las notas.

---

## 3. Estado de la Sincronización en GitHub
* Código limpio sin comentarios artificiales.
* Archivos integrados en la rama principal (`main`):
  - `application/sql/crear_tablas_rubricas_subnotas.sql`
  - `application/models/Subnotas_model.php`
  - `application/config/moodle.php`

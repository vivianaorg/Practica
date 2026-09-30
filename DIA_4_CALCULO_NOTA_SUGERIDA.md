# Bitácora Día 4: Cálculo de Nota Sugerida y Aceptación Docente

Este documento describe la formulación matemática, lógica de negocio, arquitectura y componentes de interfaz desarrollados durante la jornada del Día 4 para el cálculo automático de subnotas ponderadas desde Moodle y su aceptación/modificación por parte del docente en Divisist.

---

## 1. Objetivo de la Jornada
Implementar el módulo completo que permita al docente:
1. **Consultar en tiempo real** las calificaciones de los estudiantes en Moodle para las actividades asociadas a la rúbrica del corte seleccionado.
2. **Calcular automáticamente las subnotas** de acuerdo con la ponderación asignada a cada actividad.
3. **Calcular la Nota Sugerida** del corte normalizada a la escala institucional (0.0 a 5.0).
4. **Inspeccionar el desglose detallado** de cada estudiante mediante una ventana modal interactiva.
5. **Aceptar o modificar individual o masivamente** las notas sugeridas antes de registrarlas como definitivas.
6. **Persistir el desglose de subnotas y estados** (`SUGERIDA`, `ACEPTADA`, `MODIFICADA`) en la tabla `SUBNOTAS` de Oracle.

---

## 2. Formulación y Algoritmo de Cálculo

Para cada estudiante $e$ matriculado en Moodle y para cada actividad $i$ configurada en la rúbrica del corte:

1. **Lectura de Calificación Original de Moodle:**
   - Se obtiene $\text{NotaOriginal}_i$ y $\text{NotaMaxima}_i$ directamente desde el API/CLI de Moodle.
   - Si el estudiante no entregó o no ha sido calificado: $\text{NotaOriginal}_i = 0.0$.

2. **Normalización a Escala Institucional (0.0 a 5.0):**
   Dado que algunas actividades en Moodle se califican sobre 100, 10 o 5 puntos:
   $$\text{NotaNormalizada}_i = \begin{cases} \left( \frac{\text{NotaOriginal}_i}{\text{NotaMaxima}_i} \right) \times 5.0 & \text{si } \text{NotaMaxima}_i > 0 \text{ y } \text{NotaMaxima}_i \neq 5.0 \\ \text{NotaOriginal}_i & \text{en otro caso} \end{cases}$$

3. **Cálculo de la Subnota Ponderada:**
   Conocido el porcentaje $P_i$ asignado en la rúbrica:
   $$\text{Subnota}_i = \text{round}\left( \text{NotaNormalizada}_i \times \frac{P_i}{100},\ 2 \right)$$

4. **Nota Sugerida Final del Corte:**
   $$\text{NotaSugerida} = \min\left(5.0,\ \max\left(0.0,\ \sum_{i=1}^{n} \text{Subnota}_i\right)\right)$$

5. **Ajuste Proporcional en caso de Modificación Manual:**
   Si el docente ajusta manualmente la nota definitiva a $\text{NotaDefinitiva}$, el sistema reescala proporcionalmente cada subnota para mantener coherencia matemática en el desglose:
   $$\text{Factor} = \begin{cases} \frac{\text{NotaDefinitiva}}{\text{NotaSugerida}} & \text{si } \text{NotaSugerida} > 0 \\ 1.0 & \text{si } \text{NotaSugerida} = 0 \end{cases}$$
   $$\text{SubnotaFinal}_i = \text{round}(\text{Subnota}_i \times \text{Factor},\ 2)$$

---

## 3. Componentes Implementados

### 3.1 Modelo: `application/models/Subnotas_model.php`
* **`obtener_todas_subnotas_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)`:**
  Recupera las subnotas previamente guardadas en Oracle agrupadas por código de estudiante, permitiendo identificar si ya fueron aceptadas o modificadas.
* **`guardar_calificaciones_grupo($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $listaEstudiantes)`:**
  Procesa en lote todas las calificaciones y subnotas del corte:
  - Elimina registros anteriores del estudiante para el corte seleccionado.
  - Inserta las filas en `SUBNOTAS` con ID incremental seguro (`SELECT NVL(MAX(ID), 0) + 1`).
  - Registra el estado asignado (`ACEPTADA` o `MODIFICADA`) y la fecha de registro.

### 3.2 Controlador: `application/controllers/Dashboard.php`
* **`calificar_rubrica($courseId, $tipoPrevio)`:**
  - Valida si la rúbrica del corte está configurada y suma 100%.
  - Consume las calificaciones del curso desde Moodle a través de `Moodle_model->obtener_calificaciones($courseId)`.
  - Cruza las calificaciones con la rúbrica y calcula las subnotas y notas sugeridas.
  - Calcula estadísticas globales del curso (Aprobados, Reprobados, Promedio).
  - Carga la vista interactiva de evaluación.
* **`guardar_calificaciones_corte_ajax()`:**
  - Endpoint asíncrono para recibir el JSON con las notas definitivas y el desglose de subnotas.
  - Invoca al modelo para persistir en Oracle y retorna respuesta estructurada JSON.

### 3.3 Vista Principal: `application/views/dashboard/calificar_rubrica.php`
* **Selector de Corte:** Permite alternar entre Primer Previo, Segundo Previo, Tercer Previo y Examen Final.
* **Validación Temprana:** Si la rúbrica no suma 100%, bloquea la tabla y muestra un botón directo para configurarla.
* **Métricas en Vivo (KPI Cards):**
  - Total Estudiantes matriculados.
  - Estudiantes aprobando ($\ge 3.0$) con porcentaje.
  - Estudiantes reprobando ($< 3.0$) con porcentaje.
  - Promedio global sugerido.
* **Herramientas de Aceptación:**
  - Botón *"Aceptar Todas las Sugerencias"*: traslada automáticamente la nota sugerida al campo definitivo para todo el curso.
  - Botón individual *"Aceptar"*: copia la sugerencia para un estudiante específico.
  - Campo numérico editable (0.0 a 5.0) con detección automática de cambios.
  - Badges dinámicos de estado: `SUGERIDA` (amarillo), `ACEPTADA` (verde), `MODIFICADA` (azul).
* **Modal de Desglose de Rúbrica:**
  - Muestra nombre, código, actividades evaluadas, nota original en Moodle, nota base normalizada, porcentaje de peso y subnota aportada.

### 3.4 Puntos de Entrada y Navegación
* **`cursos_moodle.php`:** Se agregó el enlace directo *"Calificar"* en las tarjetas de cada curso.
* **`configurar_rubrica.php`:** Se agregó el botón *"Ir a Calificar Corte"* en el encabezado y en cada tarjeta de corte completado.
* **`sidebar.php`:** Nuevo acceso en el menú lateral denominado *"Calificar Rúbricas"*.

---

## 4. Comandos de Sincronización en la Máquina Virtual

Para aplicar todos los archivos de esta jornada en la máquina virtual Linux:

```bash
BASE_URL="https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist"
DESTINO="$HOME/Documentos/podman/ci3/html/basecidivisist"

curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/models/Subnotas_model.php" -o "$DESTINO/application/models/Subnotas_model.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/controllers/Dashboard.php" -o "$DESTINO/application/controllers/Dashboard.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/views/dashboard/calificar_rubrica.php" -o "$DESTINO/application/views/dashboard/calificar_rubrica.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/views/dashboard/configurar_rubrica.php" -o "$DESTINO/application/views/dashboard/configurar_rubrica.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/views/dashboard/cursos_moodle.php" -o "$DESTINO/application/views/dashboard/cursos_moodle.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/views/templates/default_template/sidebar.php" -o "$DESTINO/application/views/templates/default_template/sidebar.php"
```

---

## 5. Procedimiento de Verificación en el Navegador

1. **Acceso al módulo:**
   Ingresar a la URL:
   ```
   http://localhost:10001/dashboard/calificar_rubrica/2/1
   ```
2. **Validación de Rúbrica:**
   - Si la rúbrica del corte 1 ya suma 100%, se desplegará la tabla de estudiantes y los KPIs.
   - Si seleccionas un corte sin rúbrica (por ejemplo el corte 3), aparecerá la alerta indicando que debe configurarse primero.
3. **Inspección de Desglose:**
   - Hacer clic en el botón *"Ver Rúbrica"* en cualquier estudiante. Se abrirá el modal mostrando la calificación de Moodle de cada actividad y su subnota calculada.
4. **Aceptación o Modificación:**
   - Probar el botón individual *"Aceptar"* para copiar la nota sugerida.
   - Probar el botón masivo *"Aceptar Todas las Sugerencias"*.
   - Modificar manualmente la nota de un estudiante (ejemplo: cambiar de 3.5 a 4.0). Verificar que el badge cambie a `MODIFICADA` y que los KPIs se actualicen en tiempo real.
5. **Guardado en Base de Datos:**
   - Hacer clic en *"Guardar Calificaciones"*. Verificar la aparición de la alerta de éxito en la parte superior.
   - Recargar la página: las notas y estados guardados permanecerán persistidos en la base de datos.

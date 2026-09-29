# Bitácora Día 3: Interfaz Docente para Configuración y Visualización de Rúbricas

Este documento resume las actividades técnicas, arquitectónicas y de interfaz de usuario realizadas durante la jornada del Día 3 para la configuración, visualización integral y persistencia de rúbricas por corte evaluativo en Divisist.

---

## 1. Objetivo de la Jornada
Implementar la interfaz gráfica y los controladores en Divisist para que el docente pueda:
1. **Visualizar el estado general de las rúbricas** de todos los cortes del curso (Primer Previo, Segundo Previo, Tercer Previo y Examen Final).
2. **Seleccionar el corte a evaluar** mediante selectores dinámicos.
3. **Asignar actividades de Moodle** bloqueando automáticamente aquellas ya seleccionadas para impedir duplicidad.
4. **Definir porcentajes ponderados** garantizando una sumatoria exacta del 100% mediante una barra de progreso en tiempo real.
5. **Guardar la rúbrica en Oracle** de forma asíncrona (AJAX) garantizando integridad transaccional.

---

## 2. Decisiones Técnicas y de Arquitectura

### 2.1 Manejo de Secuencias en Oracle (`UDOCENTE`)
Durante las pruebas de inserción en Oracle se identificó el error `ORA-01031: insufficient privileges`, dado que el usuario `UDOCENTE` no posee permisos de creación de secuencias (`CREATE SEQUENCE`).
* **Solución aplicada:** Se implementó la generación incremental segura de claves primarias directamente en las consultas:
  ```sql
  SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM CONFIG_RUBRICA;
  ```
  Esto garantiza compatibilidad sin depender de privilegios de DBA.

### 2.2 Separación en Pestañas: Visualización Integral vs. Configuración
Para responder a la necesidad de inspeccionar qué actividades quedaron asignadas a cada corte evaluativo, se implementó una interfaz basada en pestañas (`nav-tabs-custom` de AdminLTE):
* **Pestaña 1: "Resumen de Rúbricas del Curso":**
  - Muestra tarjetas independientes para cada corte evaluativo (*Primer Previo*, *Segundo Previo*, *Tercer Previo*, *Examen Final*).
  - Cada tarjeta presenta:
    - Estado de configuración (Badge verde *Configurada al 100%*, o amarillo *Incompleta / Sin configurar*).
    - Tabla con las actividades asociadas, tipo (cuestionario, tarea, foro) y porcentaje asignado.
    - Barra de ponderación porcentual acumulada.
    - Botón de acceso directo *"Modificar Rúbrica"* que conmuta a la pestaña de edición precargando el corte.
    - Botón directo *"Calificar Corte"* cuando el corte se encuentra al 100%.
* **Pestaña 2: "Configurar / Editar Rúbrica":**
  - Selector del corte a editar.
  - Tabla dinámica para agregar y eliminar actividades.
  - Exclusión de duplicados en tiempo real (las opciones seleccionadas se deshabilitan en los demás selectores).
  - Barra de progreso que valida estrictamente la suma del 100%.
  - Guardado asíncrono vía `fetch` con recarga suave para reflejar cambios.

---

## 3. Archivos Desarrollados y Modificados

### 3.1 Modelo: `application/models/Subnotas_model.php`
* **`obtener_todas_rubricas_curso($codProfesor, $codMateria, $grupo, $semestre)`:**
  Consulta todas las actividades asociadas a la materia y grupo, agrupándolas en una estructura indexada por corte (`'1'`, `'2'`, `'3'`, `'FINAL'`).
* **`guardar_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio, $items)`:**
  Elimina la configuración previa del corte y registra de forma limpia las nuevas actividades con el cálculo de ID incremental.
* **`obtener_rubrica($codProfesor, $codMateria, $grupo, $semestre, $tipoPrevio)`:**
  Recupera el listado específico de actividades y porcentajes para un corte en particular.

### 3.2 Controlador: `application/controllers/Dashboard.php`
* **`configurar_rubrica($courseId, $tipoPrevio)`:**
  - Obtiene las actividades del curso desde el API de Moodle (`Moodle_model->listar_actividades`).
  - Obtiene la rúbrica del corte seleccionado y el resumen global de todos los cortes (`Subnotas_model->obtener_todas_rubricas_curso`).
  - Envía la información a la vista para alimentar ambas pestañas.
* **`guardar_rubrica_ajax()`:**
  - Valida en backend que la sumatoria de porcentajes sea exactamente 100%.
  - Registra las filas correspondientes en la tabla `CONFIG_RUBRICA`.
  - Retorna respuesta JSON estructurada con código de éxito y mensajes descriptivos.

### 3.3 Vista: `application/views/dashboard/configurar_rubrica.php`
* Panel de doble pestaña (Resumen y Edición).
* Control dinámico de exclusión de opciones duplicadas en selectores JS.
* Cálculo en tiempo real de ponderación porcentual y control del botón de guardado.
* Envío de datos vía AJAX con redirección al ancla `#tab_configurar`.

### 3.4 Vista: `application/views/dashboard/cursos_moodle.php`
* Incorporación de botones de navegación directa en las tarjetas de cada curso:
  - *Actividades*: Consulta de módulos creados en Moodle.
  - *Rúbrica*: Configuración y visualización de rúbricas por corte.
  - *Calificar*: Acceso a la evaluación y cálculo de notas sugeridas.

---

## 4. Comandos de Sincronización en la Máquina Virtual

Para actualizar los archivos en la máquina virtual Linux (ejecutar desde `~/Documentos/podman`):

```bash
BASE_URL="https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist"
DESTINO="$HOME/Documentos/podman/ci3/html/basecidivisist"

curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/models/Subnotas_model.php" -o "$DESTINO/application/models/Subnotas_model.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/controllers/Dashboard.php" -o "$DESTINO/application/controllers/Dashboard.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/views/dashboard/configurar_rubrica.php" -o "$DESTINO/application/views/dashboard/configurar_rubrica.php"
curl -H "Authorization: token $TOKEN" -sSL "$BASE_URL/application/views/dashboard/cursos_moodle.php" -o "$DESTINO/application/views/dashboard/cursos_moodle.php"
```

> **Nota:** Se utiliza `$HOME` en lugar de `"~"` entre comillas dobles para evitar el error de escritura `curl: (23)`.

---

## 5. Validación y Pruebas Realizadas

1. **Visualización Global:**
   - URL: `http://localhost:10001/dashboard/configurar_rubrica/2/1`
   - En la pestaña *"Resumen de Rúbricas del Curso"* se verificó que cada corte muestra su estado de asignación y las actividades correspondientes.
2. **Edición y Exclusión de Duplicados:**
   - En la pestaña *"Configurar / Editar Rúbrica"*, al agregar actividades se comprobó que los selectores bloquean las opciones ya asignadas.
3. **Validación del 100%:**
   - La barra de progreso permanece en amarillo si la suma es menor a 100% y en rojo si es mayor, deshabilitando el botón de guardar hasta alcanzar la suma exacta.
4. **Persistencia en Oracle:**
   - El guardado AJAX retorna `exito: true` y la tabla `CONFIG_RUBRICA` almacena los registros con IDs autoincrementales sin necesidad de secuencias.

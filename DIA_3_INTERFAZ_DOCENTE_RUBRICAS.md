# Bitácora Día 3: Interfaz Docente para Configuración de Rúbricas

Este documento resume las actividades técnicas y de interfaz de usuario realizadas durante la jornada del Día 3 para la configuración de rúbricas por corte evaluativo en Divisist.

---

## 1. Objetivo de la Jornada
Implementar la interfaz gráfica y los controladores en Divisist para que el docente pueda:
1. Seleccionar el corte a evaluar (Primer Previo, Segundo Previo, Tercer Previo o Examen Final).
2. Asignar actividades de Moodle mediante selectores dinámicos.
3. Bloquear actividades ya seleccionadas para impedir duplicidad.
4. Definir porcentajes ponderados garantizando una sumatoria exacta del 100%.
5. Guardar la rúbrica en la base de datos de forma asíncrona (AJAX).

---

## 2. Archivos Desarrollados y Modificados

### 2.1 Vista: `application/views/dashboard/configurar_rubrica.php`
* **Estilo:** AdminLTE y Bootstrap compatible con el diseño institucional de Divisist.
* **Funcionalidades interactivas:**
  - Selector de corte que recarga automáticamente la rúbrica asociada.
  - Tabla dinámica de actividades con botón para añadir y eliminar filas.
  - **Lógica de exclusión de duplicados:** Al seleccionar una actividad en cualquier fila, dicha opción se desactiva (`disabled`) en todos los demás selectores.
  - **Barra de ponderación en tiempo real:**
    - Verde: Suma exactamente 100% (habilita botón de guardar).
    - Amarillo: Suma inferior a 100% (indica los puntos porcentuales restantes).
    - Rojo: Suma superior a 100% (indica el exceso porcentual).
  - Envío de datos vía `fetch` asíncrono mostrando alertas de éxito o error sin recargar la página.

### 2.2 Controlador: `application/controllers/Dashboard.php`
* **Método `configurar_rubrica($courseId, $tipoPrevio)`:**
  - Consulta las actividades de Moodle mediante `Moodle_model->listar_actividades($courseId)`.
  - Carga la rúbrica guardada previamente en base de datos mediante `Subnotas_model->obtener_rubrica(...)`.
  - Renderiza la vista con la plantilla predeterminada de Divisist.
* **Método `guardar_rubrica_ajax()`:**
  - Valida parámetros y consistencia de los datos recibidos.
  - Verifica en el backend que la sumatoria de porcentajes sea exactamente 100%.
  - Persiste la información en la tabla `CONFIG_RUBRICA` a través de `Subnotas_model->guardar_rubrica(...)`.
  - Retorna respuesta JSON estructurada.

### 2.3 Vista: `application/views/dashboard/cursos_moodle.php`
* Se agregó el botón interactivo **"Configurar Rúbrica"** en cada tarjeta de curso para acceso directo del docente.

---

## 3. Comandos de Sincronización en la Máquina Virtual

Para aplicar estos cambios en la VM:

```bash
# 1. Descargar la vista de configuración de rúbricas
curl -H "Authorization: token TU_TOKEN_GITHUB" -sSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist/application/views/dashboard/configurar_rubrica.php -o ~/Documentos/podman/ci3/html/basecidivisist/application/views/dashboard/configurar_rubrica.php

# 2. Descargar la vista actualizada de cursos_moodle.php
curl -H "Authorization: token TU_TOKEN_GITHUB" -sSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist/application/views/dashboard/cursos_moodle.php -o ~/Documentos/podman/ci3/html/basecidivisist/application/views/dashboard/cursos_moodle.php

# 3. Descargar el controlador Dashboard.php actualizado
curl -H "Authorization: token TU_TOKEN_GITHUB" -sSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist/application/controllers/Dashboard.php -o ~/Documentos/podman/ci3/html/basecidivisist/application/controllers/Dashboard.php

# 4. Validar sintaxis en ci3-dev
podman exec -it ci3-dev sh -c "php -l /var/www/html/basecidivisist/application/controllers/Dashboard.php"
```

---

## 4. Prueba en Navegador
Acceder desde el navegador de la VM:
```
http://localhost:10001/dashboard/configurar_rubrica/2/1
```
*(O desde la lista de cursos en `http://localhost:10001/dashboard/cursos_moodle` pulsando el botón "Configurar Rúbrica").*

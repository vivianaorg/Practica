# Bitácora Día 2: API de Calificaciones en Moodle y Conexión con Divisist

Este documento detalla el desarrollo realizado durante la jornada del Día 2 para la extracción automatizada de calificaciones de estudiantes desde la plataforma Moodle (NPLad) hacia Divisist.

---

## 1. Objetivo Técnico
Construir el canal de comunicación que permita a Divisist consultar todas las notas de los estudiantes de un curso en sus respectivas actividades (tareas, cuestionarios, foros), para alimentar el cálculo de rúbricas y subnotas.

---

## 2. Componentes Desarrollados

### 2.1 Script CLI en Moodle
* **Ruta en repositorio:** `podman/nplad/scripts/cli_obtener_calificaciones.php`
* **Ruta de despliegue en Moodle:** `/var/www/html/admin/cli/obtener_calificaciones.php`
* **Tecnología:** PHP CLI nativo de Moodle 5.0 con `\core\cron::setup_user();`.

**Lógica implementada:**
1. Recibe el parámetro `--course_id`.
2. Realiza una consulta directa mediante la API de base de datos de Moodle (`$DB`) uniendo las tablas:
   - `{grade_items}`: Actividades evaluables del curso (`itemtype = 'mod'`).
   - `{grade_grades}`: Calificaciones registradas de cada alumno (`finalgrade`, `rawgrade`).
   - `{user}`: Información del estudiante (`idnumber`, `username`, `firstname`, `lastname`, `email`).
3. Estructura y agrupa los resultados por estudiante, asociando el código institucional (`idnumber` o `username`) y su listado de notas obtenidas con su respectiva nota máxima.
4. Devuelve la salida en formato JSON estándar.

---

### 2.2 Puente HTTP en Moodle
* **Ruta en repositorio:** `podman/nplad/scripts/obtener_calificaciones.php`
* **Ruta de despliegue en Moodle:** `/var/www/html/obtener_calificaciones.php`

**Lógica implementada:**
1. Valida el token de autenticación recibido mediante `hash_equals()` contra `TOKEN_ESPERADO`.
2. Sanea el parámetro `course_id`.
3. Invoca internamente mediante `shell_exec()` el script CLI usando el binario `/usr/local/bin/php`.
4. Retorna el resultado JSON a Divisist con encabezado `application/json; charset=utf-8`.

---

### 2.3 Integración en Divisist (`Moodle_model.php`)
* **Archivo modificado:** `application/models/Moodle_model.php`
* **Método agregado:**
  - `obtener_calificaciones($courseId)`: Construye la petición HTTP con el token y el ID del curso, consumiendo el nuevo endpoint de Moodle y retornando los datos decodificados.
  - Compatible 100% con PHP 5.6.

---

## 3. Comandos de Despliegue en la Máquina Virtual

Para transferir los dos archivos de Moodle al contenedor `moodle_new` en la VM:

```bash
# 1. Descargar los archivos en la VM mediante curl
mkdir -p ~/Documentos/podman/nplad/scripts
curl -H "Authorization: token TU_TOKEN_GITHUB" -sSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/nplad/scripts/obtener_calificaciones.php -o ~/Documentos/podman/nplad/scripts/obtener_calificaciones.php
curl -H "Authorization: token TU_TOKEN_GITHUB" -sSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/nplad/scripts/cli_obtener_calificaciones.php -o ~/Documentos/podman/nplad/scripts/cli_obtener_calificaciones.php

# 2. Copiar los archivos al contenedor de Moodle (moodle_new)
podman cp ~/Documentos/podman/nplad/scripts/obtener_calificaciones.php moodle_new:/var/www/html/obtener_calificaciones.php
podman cp ~/Documentos/podman/nplad/scripts/cli_obtener_calificaciones.php moodle_new:/var/www/html/admin/cli/obtener_calificaciones.php

# 3. Actualizar Moodle_model.php en Divisist
curl -H "Authorization: token TU_TOKEN_GITHUB" -sSL https://raw.githubusercontent.com/vivianaorg/Practica/main/podman/ci3/html/basecidivisist/application/models/Moodle_model.php -o ~/Documentos/podman/ci3/html/basecidivisist/application/models/Moodle_model.php
```

---

## 4. Verificación y Prueba del Endpoint

Prueba desde la consola de la VM para verificar la respuesta del puente HTTP:

```bash
podman exec -it ci3-dev sh -c "curl -s 'http://moodle_new/obtener_calificaciones.php?token=a1c8f3e92b7d4560f8e1c2a9d6b3f704c5e8a1b9d2f6e3c7a0b4d8f1e5c9a2b6&course_id=2'"
```

# Documentación Técnica: Integración Divisist ↔ Moodle (NPLad)

Este documento detalla la arquitectura, el diseño técnico, la implementación del código y las pruebas realizadas para establecer la conexión e interoperabilidad entre **Divisist** (CodeIgniter 3, PHP 5.6) y la plataforma **NPLad / Moodle 5.0**.

---

## 1. Arquitectura de la Integración

### 1.1 ¿Por qué comunicación HTTP y no conexión directa a Base de Datos?
Moodle administra una estructura relacional altamente compleja con lógica interna crítica (árbol de contextos `mdl_context`, permisos, libros de calificaciones, inscripciones y cachés). Escribir o consultar directamente en la base de datos MariaDB de Moodle desde un sistema externo como Divisist rompería la integridad de datos y eventos del aula virtual.

Por esta razón, la arquitectura desacopla ambos sistemas mediante una **API HTTP segura basada en cURL y scripts puente**, garantizando que sea el propio motor de Moodle quien gestione sus entidades.

### 1.2 Flujo General de Comunicación

```
┌─────────────────────────────────┐
│     ORACLE (BD Institucional)   │  <- Fuente de verdad académica (SIA.D2_GRUPO_CARGADO)
└────────────────▲────────────────┘
                 │ SQL / OCI8 (Database2.php)
┌────────────────▼────────────────┐
│      DIVISIST (ci3-dev)         │  <- CodeIgniter 3 (PHP 5.6)
│  - Config: config/moodle.php    │
│  - Modelo: Moodle_model.php     │
└────────────────┬────────────────┘
                 │ HTTP GET + Token secreto (cURL)
┌────────────────▼────────────────┐
│     MOODLE (moodle_new)         │  <- NPLad / Moodle 5.0
│  1. Puente HTTP (/var/www/html) │  <- Valida token (hash_equals)
│  2. Script CLI (admin/cli/)     │  <- Ejecuta API oficial de Moodle ($DB)
└────────────────┬────────────────┘
                 │ Acceso interno PHP API
┌────────────────▼────────────────┐
│       MARIADB (moodle_db)       │  <- Base de datos exclusiva de Moodle
└─────────────────────────────────┘
```

---

## 2. Implementación Lado Divisist (CodeIgniter 3)

### 2.1 Archivo de Configuración Centralizada
Para evitar acoplamiento con nombres de contenedores locales (`moodle_new`) y permitir un paso limpio a producción, la configuración se desacopló en:

* **Archivo:** `application/config/moodle.php`

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Configuración de Integración Divisist - Moodle (NPLad)
|--------------------------------------------------------------------------
| Permite alternar fácilmente entre el entorno de desarrollo local (Podman)
| y el entorno de producción en los servidores de la universidad.
*/

$config['moodle_base_url']   = 'http://moodle_new'; // En producción: 'https://nplad.ufps.edu.co'
$config['moodle_token']      = 'a1c8f3e92b7d4560f8e1c2a9d6b3f704c5e8a1b9d2f6e3c7a0b4d8f1e5c9a2b6';
$config['moodle_timeout']    = 30;
$config['moodle_ssl_verify'] = false;               // En producción: true
```

### 2.2 Modelo de Conexión (`Moodle_model.php`)
Encargado de construir las peticiones HTTP seguras y procesar las respuestas JSON.
* **Archivo:** `application/models/Moodle_model.php`
* **Compatibilidad:** 100% compatible con **PHP 5.6** (sintaxis estricta `array()`, sin operadores modernos como null coalescing `??`).

**Métodos disponibles:**
1. `listar_cursos()`: Consulta todos los cursos disponibles en Moodle.
2. `listar_actividades($courseId)`: Obtiene las secciones, tareas, cuestionarios y foros de un curso específico.
3. `buscar_curso_por_codigo($codigo)`: Busca la equivalencia entre el código académico de Oracle (ej. `1155304-A`) y el `shortname` en Moodle.
4. `importar_materia($materiaId)`: Ejecuta la rutina de sincronización/creación de cursos.

**Manejo de peticiones cURL:**
```php
private function _llamar_endpoint($url, $timeout = null)
{
    if ($timeout === null) {
        $timeout = $this->timeout;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => $this->ssl_verify,
    ));

    $respuesta = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    // Validación de errores de red y decodificación de JSON
    ...
}
```

### 2.3 Controlador y Vistas
* **Controlador:** `application/controllers/Dashboard.php`
  - Método `cursos_moodle()`: Carga el listado general de cursos.
  - Método `actividades_moodle($courseId)`: Carga las tareas y actividades del curso seleccionado.
* **Vistas:**
  - `application/views/dashboard/cursos_moodle.php`
  - `application/views/dashboard/actividades_moodle.php`

---

## 3. Implementación Lado Moodle (NPLad)

En Moodle se implementó un patrón de **dos capas**:

### 3.1 Capa 1: Puentes HTTP Web (`/var/www/html/`)
Archivos ligeros expuestos vía web que actúan como guardianes de seguridad:
* `listar_cursos.php`
* `listar_actividades.php`
* `buscar_curso.php`
* `ejecutar.php`

**Protocolo de Seguridad en cada puente:**
1. **Validación de Token:** Compara el token recibido contra el token maestro usando `hash_equals()` para prevenir ataques de temporización (*timing attacks*).
2. **Saneamiento:** Limpia y valida parámetros (`escapeshellarg()`).
3. **Invocación Segura:** Ejecuta el script CLI interno correspondiente usando `/usr/local/bin/php`.
4. **Respuesta:** Devuelve un JSON estructurado estándar:
   ```json
   {
       "exito": true,
       "datos": { ... },
       "mensaje": "Mensaje opcional"
   }
   ```

### 3.2 Capa 2: Scripts CLI (`/var/www/html/admin/cli/`)
Scripts que se ejecutan en el contexto de consola de Moodle, con acceso total a las APIs internas:
* Utilizan `define('CLI_SCRIPT', true);` y cargan el entorno `config.php` de Moodle.
* Inicializan el usuario del sistema con `\core\cron::setup_user();` (estándar de Moodle 5.0).
* Emplean la API global `$DB` y funciones como `get_fast_modinfo($courseId)` para recopilar los módulos y actividades.

---

## 4. Guía de Pruebas y Verificación

### 4.1 Verificación de Sintaxis PHP 5.6 en Divisist
```bash
podman exec -it ci3-dev sh -c "php -l /var/www/html/basecidivisist/application/config/moodle.php"
podman exec -it ci3-dev sh -c "php -l /var/www/html/basecidivisist/application/models/Moodle_model.php"
```

### 4.2 Verificación de Comunicación entre Contenedores (Red Podman)
Prueba de consumo directo del endpoint desde el contenedor de Divisist:
```bash
podman exec -it ci3-dev sh -c "curl -s 'http://moodle_new/listar_cursos.php?token=a1c8f3e92b7d4560f8e1c2a9d6b3f704c5e8a1b9d2f6e3c7a0b4d8f1e5c9a2b6'"
```

### 4.3 Verificación Visual en Divisist
Accediendo con sesión iniciada en el navegador:
* Listado de cursos: `http://localhost:10001/dashboard/cursos_moodle`
* Actividades de un curso: `http://localhost:10001/dashboard/actividades_moodle/2`

---

## 5. Diferencias entre Entornos (Desarrollo vs Producción)

| Parámetro | Entorno de Desarrollo (VM Podman) | Entorno de Producción (Servidores UFPS) |
|---|---|---|
| **Base URL** | `http://moodle_new` (o `http://localhost:8080`) | `https://nplad.ufps.edu.co` |
| **Protocolo** | HTTP interno | HTTPS institucional |
| **Verificación SSL** | `false` | `true` |
| **Resolución de Red** | Red Podman (`fedorapracticante_default`) | DNS Institucional / Red Universitaria |
| **Seguridad Adicional** | Validación de Token | Validación de Token + Restricción por IP de origen |

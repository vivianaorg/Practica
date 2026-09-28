<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Configuración de Integración Divisist - Moodle (NPLad)
|--------------------------------------------------------------------------
|
| moodle_base_url:
|   - En desarrollo (Podman local VM): 'http://moodle_new' (o 'http://localhost:8080')
|   - En producción (Servidores UFPS): 'https://nplad.ufps.edu.co'
|
| moodle_token:
|   - Token compartido de autenticación entre Divisist y los puentes HTTP de Moodle.
|
| moodle_timeout:
|   - Tiempo máximo de espera en segundos para las peticiones cURL (por defecto 30).
|
| moodle_ssl_verify:
|   - false en desarrollo local sin certificados SSL.
|   - true en producción para validar certificados HTTPS.
|
*/

$config['moodle_base_url']   = 'http://moodle_new';
$config['moodle_token']      = 'a1c8f3e92b7d4560f8e1c2a9d6b3f704c5e8a1b9d2f6e3c7a0b4d8f1e5c9a2b6';
$config['moodle_timeout']    = 30;
$config['moodle_ssl_verify'] = false;

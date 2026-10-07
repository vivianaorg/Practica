<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
  | -------------------------------------------------------------------------
  | URI ROUTING
  | -------------------------------------------------------------------------
  | This file lets you re-map URI requests to specific controller functions.
  |
  | Typically there is a one-to-one relationship between a URL string
  | and its corresponding controller class/method. The segments in a
  | URL normally follow this pattern:
  |
  |	example.com/class/method/id/
  |
  | In some instances, however, you may want to remap this relationship
  | so that a different class/function is called than the one
  | corresponding to the URL.
  |
  | Please see the user guide for complete details:
  |
  |	http://codeigniter.com/user_guide/general/routing.html
  |
  | -------------------------------------------------------------------------
  | RESERVED ROUTES
  | -------------------------------------------------------------------------
  |
  | There are three reserved routes:
  |
  |	$route['default_controller'] = 'welcome';
  |
  | This route indicates which controller class should be loaded if the
  | URI contains no data. In the above example, the "welcome" class
  | would be loaded.
  |
  |	$route['404_override'] = 'errors/page_missing';
  |
  | This route will tell the Router which controller/method to use if those
  | provided in the URL cannot be matched to a valid route.
  |
  |	$route['translate_uri_dashes'] = FALSE;
  |
  | This is not exactly a route, but allows you to automatically route
  | controller and method names that contain dashes. '-' isn't a valid
  | class or method name character, so it requires translation.
  | When you set this option to TRUE, it will replace ALL dashes in the
  | controller and method URI segments.
  |
  | Examples:	my-controller/index	-> my_controller/index
  |		my-controller/my-method	-> my_controller/my_method
 */
$route['default_controller'] = 'sesion/login';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['notification/pag/(:num)'] = 'notification/index/$1';
$route['login'] = 'sesion/login';
$route['logout'] = 'sesion/logout';

// Rutas amigables Moodle
$route['moodle'] = 'moodle/cursos';
$route['moodle/cursos'] = 'moodle/cursos';
$route['moodle/actividades'] = 'moodle/actividades';
$route['moodle/actividades/(:any)'] = 'moodle/actividades/$1';

// Rutas amigables Calificaciones y Rubricas
$route['calificaciones'] = 'calificaciones/index';
$route['calificaciones/index'] = 'calificaciones/index';
$route['calificaciones/index/(:any)'] = 'calificaciones/index/$1';
// Endpoints AJAX para Rúbricas (nuevo controlador separado)
$route['calificaciones/guardar_rubrica_ajax'] = 'calificaciones_rubrica_ajax/guardar_rubrica_ajax';
$route['calificaciones/eliminar_rubrica_ajax'] = 'calificaciones_rubrica_ajax/eliminar_rubrica_ajax';
$route['calificaciones/desbloquear_corte_ajax'] = 'calificaciones_rubrica_ajax/desbloquear_corte_ajax';
$route['calificaciones/listar_cursos_rubricas_ajax'] = 'calificaciones_rubrica_ajax/listar_cursos_rubricas_ajax';
$route['calificaciones/obtener_items_rubrica_ajax'] = 'calificaciones_rubrica_ajax/obtener_items_rubrica_ajax';
// Endpoints AJAX para Calificaciones de notas (nuevo controlador separado)
$route['calificaciones/guardar_calificaciones_corte_ajax'] = 'calificaciones_notas_ajax/guardar_calificaciones_corte_ajax';
// Retrocompatibilidad con dashboard/ para AJAX de rúbricas
$route['dashboard/listar_cursos_rubricas_ajax'] = 'calificaciones_rubrica_ajax/listar_cursos_rubricas_ajax';
$route['dashboard/obtener_items_rubrica_ajax'] = 'calificaciones_rubrica_ajax/obtener_items_rubrica_ajax';

$route['calificaciones/configurar_rubrica'] = 'calificaciones/configurar_rubrica';
$route['calificaciones/configurar_rubrica/(:any)'] = 'calificaciones/configurar_rubrica/$1';
$route['calificaciones/configurar_rubrica/(:any)/(:any)'] = 'calificaciones/configurar_rubrica/$1/$2';
$route['calificaciones/calificar_rubrica'] = 'calificaciones/calificar_rubrica';
$route['calificaciones/calificar_rubrica/(:any)'] = 'calificaciones/calificar_rubrica/$1';
$route['calificaciones/calificar_rubrica/(:any)/(:any)'] = 'calificaciones/calificar_rubrica/$1/$2';
$route['calificaciones/(:num)'] = 'calificaciones/index/$1';

// Retrocompatibilidad con URLs anteriores bajo dashboard/
$route['dashboard/cursos_moodle'] = 'moodle/cursos';
$route['dashboard/actividades_moodle'] = 'moodle/actividades';
$route['dashboard/actividades_moodle/(:any)'] = 'moodle/actividades/$1';

$route['dashboard/configurar_rubrica'] = 'calificaciones/configurar_rubrica';
$route['dashboard/configurar_rubrica/(:any)'] = 'calificaciones/configurar_rubrica/$1';
$route['dashboard/configurar_rubrica/(:any)/(:any)'] = 'calificaciones/configurar_rubrica/$1/$2';
$route['dashboard/guardar_rubrica_ajax'] = 'calificaciones_rubrica_ajax/guardar_rubrica_ajax';
$route['dashboard/eliminar_rubrica_ajax'] = 'calificaciones_rubrica_ajax/eliminar_rubrica_ajax';
$route['dashboard/desbloquear_corte_ajax'] = 'calificaciones_rubrica_ajax/desbloquear_corte_ajax';

$route['dashboard/calificar_rubrica'] = 'calificaciones/calificar_rubrica';
$route['dashboard/calificar_rubrica/(:any)'] = 'calificaciones/calificar_rubrica/$1';
$route['dashboard/calificar_rubrica/(:any)/(:any)'] = 'calificaciones/calificar_rubrica/$1/$2';
$route['dashboard/guardar_calificaciones_corte_ajax'] = 'calificaciones_notas_ajax/guardar_calificaciones_corte_ajax';

$route['dashboard/calificaciones'] = 'calificaciones/index';
$route['dashboard/calificaciones/(:any)'] = 'calificaciones/index/$1';

$route['dashboard/test_oracle'] = 'diagnostico/test_oracle';
$route['dashboard/resumen_esquemas'] = 'diagnostico/resumen_esquemas';
$route['dashboard/dump_esquema'] = 'diagnostico/dump_esquema';
$route['dashboard/dump_esquema/(:any)'] = 'diagnostico/dump_esquema/$1';
$route['dashboard/ver_grupo_cargado'] = 'diagnostico/ver_grupo_cargado';
$route['dashboard/ver_grupo_cargado/(:any)'] = 'diagnostico/ver_grupo_cargado/$1';


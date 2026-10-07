<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper para utilidades de profesor
 */

if (!function_exists('obtener_cod_profesor')) {
    /**
     * Obtiene el código del profesor desde la sesión, POST o GET, con fallback a '04608'
     *
     * @param object $ci_instance Instancia de CodeIgniter
     * @return string Código del profesor
     */
    function obtener_cod_profesor($ci_instance) {
        $codProfesor = (isset($ci_instance->usuario) && isset($ci_instance->usuario->CODIGO)) 
            ? $ci_instance->usuario->CODIGO : '04608';
        if ($ci_instance->input->post('cod_profesor')) {
            $codProfesor = $ci_instance->input->post('cod_profesor');
        } elseif ($ci_instance->input->get('cod_profesor')) {
            $codProfesor = $ci_instance->input->get('cod_profesor');
        }
        return $codProfesor;
    }
}

if (!function_exists('obtener_semestre_actual')) {
    /**
     * Obtiene el semestre académico actual
     *
     * @return string Semestre actual (ej. 2023-1)
     */
    function obtener_semestre_actual() {
        return date('Y') . '-' . (date('n') <= 6 ? '1' : '2');
    }
}

if (!function_exists('normalizar_grupo')) {
    /**
     * Normaliza un grupo vacío a '-'
     *
     * @param string $grupo Grupo a normalizar
     * @return string Grupo normalizado
     */
    function normalizar_grupo($grupo) {
        return (!empty($grupo) && $grupo !== '') ? $grupo : '-';
    }
}

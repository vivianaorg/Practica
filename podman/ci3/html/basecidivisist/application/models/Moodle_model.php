<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Moodle_model extends CI_Model
{
    private $base_url;
    private $token;
    private $timeout;
    private $ssl_verify;

    public function __construct()
    {
        parent::__construct();
        $this->load->config('moodle', TRUE);

        $this->base_url   = rtrim($this->config->item('moodle_base_url', 'moodle'), '/');
        $this->token      = $this->config->item('moodle_token', 'moodle');
        $this->timeout    = (int) $this->config->item('moodle_timeout', 'moodle');
        $this->ssl_verify = (bool) $this->config->item('moodle_ssl_verify', 'moodle');

        if (empty($this->timeout)) {
            $this->timeout = 30;
        }
    }

    public function importar_materia($materiaId)
    {
        $url = $this->base_url . '/ejecutar.php?' . http_build_query(array(
            'token'      => $this->token,
            'materia_id' => $materiaId,
        ));

        return $this->_llamar_endpoint($url);
    }

    public function listar_actividades($courseId)
    {
        $url = $this->base_url . '/listar_actividades.php?' . http_build_query(array(
            'token'     => $this->token,
            'course_id' => $courseId,
        ));

        return $this->_llamar_endpoint($url);
    }

    public function listar_cursos()
    {
        $url = $this->base_url . '/listar_cursos.php?' . http_build_query(array(
            'token' => $this->token,
        ));

        return $this->_llamar_endpoint($url);
    }

    public function buscar_curso_por_codigo($codigo)
    {
        $url = $this->base_url . '/buscar_curso.php?' . http_build_query(array(
            'token'  => $this->token,
            'codigo' => $codigo,
        ));

        return $this->_llamar_endpoint($url);
    }

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

        if ($error) {
            log_message('error', 'Moodle_model: error cURL -> ' . $error);
            return array(
                'exito'   => false,
                'datos'   => null,
                'mensaje' => 'No se pudo conectar con Moodle: ' . $error,
            );
        }

        $decodificado = json_decode($respuesta, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Moodle_model: respuesta no es JSON válido -> ' . $respuesta);
            return array(
                'exito'   => false,
                'datos'   => null,
                'mensaje' => 'Respuesta inválida de Moodle (HTTP ' . $httpCode . ')',
            );
        }

        return $decodificado;
    }
}
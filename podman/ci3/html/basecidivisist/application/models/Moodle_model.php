<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Moodle_model extends CI_Model
{
    private $base_url = 'http://moodle_new';
    private $token    = 'a1c8f3e92b7d4560f8e1c2a9d6b3f704c5e8a1b9d2f6e3c7a0b4d8f1e5c9a2b6';

    public function importar_materia($materiaId)
    {
        $url = $this->base_url . '/ejecutar.php?' . http_build_query([
            'token'      => $this->token,
            'materia_id' => $materiaId,
        ]);

        return $this->_llamar_endpoint($url);
    }

    public function listar_actividades($courseId)
    {
        $url = $this->base_url . '/listar_actividades.php?' . http_build_query([
            'token'     => $this->token,
            'course_id' => $courseId,
        ]);

        return $this->_llamar_endpoint($url);
    }

        public function listar_cursos()
    {
        $url = $this->base_url . '/listar_cursos.php?' . http_build_query(array(
            'token' => $this->token,
        ));

        return $this->_llamar_endpoint($url);
    }

    private function _llamar_endpoint($url, $timeout = 30)
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $respuesta = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', 'Moodle_model: error cURL -> ' . $error);
            return [
                'exito'   => false,
                'datos'   => null,
                'mensaje' => 'No se pudo conectar con Moodle: ' . $error,
            ];
        }

        $decodificado = json_decode($respuesta, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Moodle_model: respuesta no es JSON válido -> ' . $respuesta);
            return [
                'exito'   => false,
                'datos'   => null,
                'mensaje' => 'Respuesta inválida de Moodle (HTTP ' . $httpCode . ')',
            ];
        }

        return $decodificado;
    }
}
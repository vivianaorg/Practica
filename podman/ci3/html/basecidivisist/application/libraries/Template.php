<?php

class Template {

    protected $_CI;
    private $_configs;
    private $_data;
    private $_js;
    private $_css;
    private $_message;

    function __construct() {
        $this->_CI = & get_instance();
        $this->_configs = $this->_CI->config->item('template');
        $this->_data = array();
        $this->_css = array();
        $this->_js = array();
    }

    /**
     * Define una variable, objeto o arreglo para establecer en la vista renderizada por un controlador.
     * @param String $key - llave de la variable
     * @param type $value - valor de la variable
     */
    public function set($key = NULL, $value = NULL) {

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_data[$k] = $v;
            }
        } elseif ($key) {
            $this->_data[$key] = $value;
        }
    }

    /**
     * Modifica la plantilla sobre la cual se renderizan vistas. Se recomienda ser utilizada en el constructor de
     * un controlador.
     * 
     * @param $String $view - Nombre de la plantilla a utilizar (sin extención), debe estar ubicada en views/templates/<b>NOMBRE_PLANTILLA</b>.php
     */
    public function set_template($view = FALSE) {
        if ($view) {
            $this->_configs['default'] = $view;
        }
    }

    public function render($view = null) {
        $template = "templates/{$this->_configs['default']}.php";
        $routes = [];

        if (!empty($view)) {
            if (!is_array($view)) {
                $view = [$view];
            }

            foreach ($view as $file) {
                if (file_exists(APPPATH . "views/templates/{$file}.php")) {
                    $routes[] = APPPATH . "views/templates/{$file}.php";
                } elseif (file_exists(APPPATH . "views/{$file}.php")) {
                    $routes[] = APPPATH . "views/{$file}.php";
                } else {
                    show_error('View error');
                }
            }
        }

        $this->_set_assets();
        $this->_set_messages();
        $this->_data['_content'] = $routes;
        $this->_CI->load->view($template, $this->_data);
    }

    /**
     * Añade un archivo CSS en la plantilla a renderizar en la pantalla, recibe como parametro la ruta dentro de la carpeta assets
     * sin la extención del archivo.
     * 
     * @param String $value
     */
    public function add_css($value) {
        $this->_add_asset($value, 'css');
    }
    
    /**
     * Añade un archivo CSS en la plantilla a renderizar en la pantalla, recibe como parametro la ruta dentro de la carpeta assets
     * sin la extención del archivo.
     * 
     * @param String $value
     */
    public function add_js($value) {
        $this->_add_asset($value, 'js');
    }

    /**
     * Función que añade un mensaje para que sea mostrado dentro de la plantilla actual.
     * Para que este metodo funcione la plantilla en la que se estan renderizando las vistas debe contener un bloque de código
     * que muestre los mensajes capturados utilizando este método.
     * 
     * ejemplo de uso:
     * $this->template->add_message(array("error" => "Ocurrio un error inesperado"));
     * 
     * @param array $message - arreglo asociativo de tipo array("tipo_mensaje" => "contenido_mensaje")
     * @param String $_type
     */
    public function add_message($message, $_type = NULL) {
        $this->_add_message($message, $_type);
    }

    /**
     * Función que añade un mensaje para que sea mostrado dentro de la plantilla actual 
     * despues de un redireccionamiento de página.
     * 
     * Ejemplo de uso:
     * $this->template->set_flash_message(array("Ocurrio un error inesperado"),"error");
     * 
     * @param array $message - Arreglo de mensajes
     * @param String $type - Tipo de mensaje
     */
    public function set_flash_message(array $message, $type = 'info') {
        if (sizeof($message) > 0) {
            $this->_CI->session->set_userdata('_messagetype_', $type);
            $this->_CI->session->set_userdata('_message_', $message);
            $this->_CI->session->mark_as_flash(array('_messagetype_', '_message_'));
//            $this->_CI->session->set_flashdata('_messagetype_', $type);
//            $this->_CI->session->set_flashdata('_message_', $message);
        }
    }

    private function _set_messages() {
        $this->_add_message(array($this->_CI->session->flashdata('_messagetype_') => $this->_CI->session->flashdata('_message_')));
        $this->_data['_warning'] = isset($this->_message['warning']) ? $this->_message['warning'] : [];
        $this->_data['_success'] = isset($this->_message['success']) ? $this->_message['success'] : [];
        $this->_data['_error'] = isset($this->_message['error']) ? $this->_message['error'] : [];
        $this->_data['_info'] = isset($this->_message['info']) ? $this->_message['info'] : [];
    }

    private function _add_message($message, $_type = NULL) {

        if (!empty($message)) {

            $types = ['warning', 'success', 'error', 'info'];
            $check_type = function($_type) use ($types) {
                return (empty($_type) || !in_array($_type, $types)) ? 'info' : $_type;
            };

            if (is_array($message)) {
                foreach ($message as $_type => $msj) {
                    if (!empty($msj)) {
                        $type = $check_type($_type);
                        if (is_array($msj)) {
                            foreach ($msj as $_msj) {
                                if (!empty($_msj)) {
                                    $this->_message[$type][] = (string) $_msj;
                                }
                            }
                        } else {
                            $this->_message[$type][] = (string) $msj;
                        }
                    }
                }
            } else {
                $type = $check_type($_type);
                $this->_message[$type][] = (string) $message;
            }
        }
    }

    private function _add_asset($value, $asset_type) {
        $asset = [];
        if (is_array($value)) {
            foreach ($value as $val) {
                $asset[] = $val;
            }
        } else {
            $asset[] = $value;
        }

        if ($asset_type == 'js') {
            $this->_js = array_merge($asset, $this->_js);
        } elseif ($asset_type == 'css') {
            $this->_css = array_merge($asset, $this->_css);
        }
    }

    private function _set_assets() {

        if (isset($this->_configs['js']) && sizeof($this->_configs['js']) > 0) {
            $this->_add_asset($this->_configs['js'], 'js');
        }

        if (isset($this->_configs['css']) && sizeof($this->_configs['css']) > 0) {
            $this->_add_asset($this->_configs['css'], 'css');
        }

        $_css = $_js = '';

        if (sizeof($this->_js) > 0) {
            foreach ($this->_js as $js) {
                $src = base_url() . 'assets/' . $js;
                $_js .= sprintf('<script type="text/javascript" src="%s.js"></script>', $src);
            }
        }

        if (sizeof($this->_css) > 0) {
            foreach ($this->_css as $css) {
                $href = base_url() . 'assets/' . $css;
                $_css .= sprintf('<link type="text/css" rel="stylesheet" href="%s.css">', $href);
            }
        }

        $this->_data['_js'] = $_js;
        $this->_data['_css'] = $_css;
    }

}

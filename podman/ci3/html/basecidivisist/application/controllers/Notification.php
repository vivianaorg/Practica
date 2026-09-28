<?php

class Notification extends CMS_Controller {

    function __construct() {
        parent::__construct();

        $this->load->model('notifications_model');
        $this->template->set_template('default_template');
        $this->template->add_css('css/adminlte/skins/skin-red-light.min');
        $this->template->add_css('css/adminlte/skins/_all-skins.min');
    }

    public function index() {

        echo "Esta vista muestra un mensaje y detiene ja ejecución del controlador <BR> <a href = '" . base_url() . "' >Volver a la página principal</a>";
        exit;

        //CÓDIGO DE DIVISIST 2 

        $notifications = $this->notifications_model->get_notifications($estudiante->CODIGO);

        if ($this->unread_notifications) {
            $this->template->add_message(array('info' => 'Los mensajes "No leidos" serán marcados como "Leidos" automaticamente cuando abandone este módulo.'));
            $this->notifications_model->read_notifications($estudiante->CODIGO);
        }

        $this->template->add_js("js/views/notification/notification.min");
        $this->template->add_js("plugins/datatables/js/dataTables.bootstrap.min");
        $this->template->add_js("plugins/datatables/js/jquery.dataTables.min");

        $this->template->add_css("plugins/datatables/css/dataTables.bootstrap.min");

        $this->template->set('notifications', $notifications);

        $this->template->set('content_header', 'Notificaciones');
        $this->template->set('content_sub_header', '');
        $this->template->set('item_sidebar_active', 'notification');
        $this->template->render('notification/index');
    }

}

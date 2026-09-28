<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$config['template']['default'] = 'default_template';

$config['template']['js'] = array(
    'js/jquery/jQuery-2.1.3.min',
    'js/bootstrap/bootstrap.min',
    'js/adminlte/app.min',
//    'plugins/slimScroll/jquery.slimscroll.min',
//    'plugins/fastclick/fastclick.min',
//    'plugins/iCheck/icheck.min',
//    'js/adminlte/demo'
);

$config['template']['css'] = array(
    'css/bootstrap/bootstrap.min',    
//    'css/bootstrap/bootstrap-theme.min',
    'css/fontawesome/css/font-awesome.min',
    'css/hovermaster/hover-min',
    'css/adminlte/AdminLTE.min',    
//    'css/adminlte/skins/skin-red-light.min',
//    'css/adminlte/skins/_all-skins.min',  
//    'plugins/iCheck/square/blue',  
    'css/custom',
);

/* End of file template.php */
/* Location: ./application/config/template.php */
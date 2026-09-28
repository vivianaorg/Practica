<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$tnsname_produccion = "(DESCRIPTION = 
            (ADDRESS = 
            (PROTOCOL = TCP)
            (HOST = 192.168.13.39)
            (PORT = 1522)) 
            (CONNECT_DATA = 
            (SERVER = DEDICATED) 
            (SERVICE_NAME = UFPS) 
            (INSTANCE_NAME = UFPS2)))";

$tnsname_pruebas = "(DESCRIPTION = 
            (ADDRESS = 
            (PROTOCOL = TCP)
            (HOST = 192.168.13.37)
            (PORT = 1521)) 
            (CONNECT_DATA = 
            (SERVER = DEDICATED) 
            (SERVICE_NAME = ORCL)))";

$config['database2']['hostname'] = $tnsname_pruebas;
$config['database2']['username'] = 'UDOCENTE';
$config['database2']['password'] = 'UDOCENTE2024';
//$config['database2']['database'] = 'UFPS2';
$config['database2']['char_set'] = 'utf8';


/* End of file database2.php */
/* Location: ./application/config/database2.php */
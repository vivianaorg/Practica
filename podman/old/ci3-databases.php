<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'   => '',
    'hostname' => 'localhost',
    'username' => '',
    'password' => '',
    'database' => '',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

// Conexion MySQL
$db['mysql'] = array(
    'dsn'   => '',
    'hostname' => getenv('MYSQL_HOST') ?: 'ci3-mysql',
    'username' => getenv('MYSQL_USER') ?: 'ci3user',
    'password' => getenv('MYSQL_PASSWORD') ?: 'ci3pass',
    'database' => getenv('MYSQL_DATABASE') ?: 'ci3db',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

// Conexion PostgreSQL
$db['pgsql'] = array(
    'dsn'   => '',
    'hostname' => getenv('PGSQL_HOST') ?: 'ci3-pgsql',
    'port'     => getenv('PGSQL_PORT') ?: '5432',
    'username' => getenv('PGSQL_USER') ?: 'ci3user',
    'password' => getenv('PGSQL_PASSWORD') ?: 'ci3pass',
    'database' => getenv('PGSQL_DATABASE') ?: 'ci3db',
    'dbdriver' => 'postgre',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

// Conexion Oracle (driver nativo oci8 de CI3, usa oci_connect con OCI8)
$db['oracle'] = array(
    'dsn'   => '//' . (getenv('ORACLE_HOST') ?: '192.168.13.37') . ':' . (getenv('ORACLE_PORT') ?: '1521') . '/' . (getenv('ORACLE_SID') ?: 'orcl'),
    'hostname' => getenv('ORACLE_HOST') ?: '192.168.13.37',
    'port'     => getenv('ORACLE_PORT') ?: '1521',
    'username' => getenv('ORACLE_USER') ?: '',
    'password' => getenv('ORACLE_PASSWORD') ?: '',
    'database' => getenv('ORACLE_SID') ?: 'orcl',
    'dbdriver' => 'oci8',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

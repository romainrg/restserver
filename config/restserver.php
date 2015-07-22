<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['restserver'] = array(
    'allow_methods' => array('GET', 'POST', 'PUT', 'DELETE'),
    'allow_headers' => array('authorization', 'content-type', 'x-requested-with'),
    'allow_credentials' => FALSE,
    'allow_origin' => FALSE,
    'force_https' => FALSE,
    'ajax_only' => FALSE,
    'auth_http' => FALSE,
    'log' => FALSE,
    'log_driver' => 'file',
    'log_db_name' => 'rest', // Database only
    'log_db_table' => 'log', // Database only
    'log_file_path' => '', // File only
    'log_file_name' => '', // File only
    'log_extra' => FALSE
);
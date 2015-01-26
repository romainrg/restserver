<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.7 (20150125)
 */
$config['restserver'] = array(
    'allow_methods' => array('GET', 'POST', 'PUT', 'DELETE'),
    'allow_headers' => array('authorization', 'key', 'content-type', 'x-requested-with'),
    'allow_credentials' => FALSE,
    'allow_origin' => FALSE,
    'force_https' => FALSE,
    'ajax_only' => FALSE,
    'auth_http' => FALSE,
    'log' => TRUE,
    'log_driver' => 'database',
    'log_path' => "",
    'log_extra' => TRUE
);

/* End of file restcontroller.php */
/* Location: ./application/config/restcontroller.php */
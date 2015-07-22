<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link https://github.com/maltyxx/restserver
 */
interface Restserver_interface
{
    public function post();
    public function get();
    public function put();
    public function delete();
}

/* End of file Restserver_interface.php */
/* Location: ./libraries/Restserver/Restserver_interface.php */

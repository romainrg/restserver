<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */
interface Restserver_interface {

    public function post();
    
    public function get();
    
    public function put();
    
    public function delete();
}

/* End of file Restserver_interface.php */
/* Location: ./application/libraries/Restserver_interface.php */

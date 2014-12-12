<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.5 (20141212)
 */
require(APPPATH.'/libraries/Restserver/Restserver_interface.php');

abstract class Restserver_Controller extends CI_Controller implements Restserver_interface {    
    public function __construct() {
        parent::__construct();
        
		$this->load->library('restserver');
    }
    
    public function _remap($call, $params = array()) {
        $this->restserver->run($this, $call, $params);
    }
}

/* End of file Restserver_Controller.php */
/* Location: ./application/core/Restserver_Controller.php */
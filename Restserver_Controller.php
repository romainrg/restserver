<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.7 (20150125)
 */

// Interface
require(APPPATH.'/libraries/Restserver/Restserver_interface.php');

abstract class Restserver_Controller extends MY_Controller implements Restserver_interface {    
    
    public function __construct() {
        parent::__construct();
        
        // Dépendances
		$this->load->library('restserver');
    }
    
    /**
     * Remap
     * @param string $call
     * @param array $params
     */
    public function _remap($call, array $params = array()) {
        $this->restserver->run($this, $call, $params);
    }
    
}

/* End of file Restserver_Controller.php */
/* Location: ./application/core/Restserver_Controller.php */
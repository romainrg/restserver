<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.6 (20150112)
 */

// Interface
require(APPPATH.'/libraries/Restserver/Restserver_interface.php');

abstract class Restserver_Controller extends CI_Controller implements Restserver_interface {    
    
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
    
    /**
     * Authentification
     * @return boolean
     */
    public function _auth() {
        $username = $this->restserver->input('username');
        $password = $this->restserver->input('password');
        
        if ($username == 'test' && $password == 'test') {
            return TRUE;
            
        // Si il y a une erreur
        } else {            
            // Prépare la réponse
            $this->restserver->response(array(
                'status' => FALSE,
                'error' => "Vos identifiants sont invalides"
            ), 406);
            
            return FALSE;
        }
    }
}

/* End of file Restserver_Controller.php */
/* Location: ./application/core/Restserver_Controller.php */
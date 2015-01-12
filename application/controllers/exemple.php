<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.6 (20150112)
 */
class Exemple extends Restserver_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->restserver->add_field(new Restserver_field(array(
            'input' => 'nom',
            'alias' => 'user_model.lastname|thewebuser_model.nom',
            'rules' => 'required_post|integer',
            'name' => 'Nom de famille',
            'comment' => "Ce champ est un entier il est obligatoire pour la méthode POST")
        ));
    }
    
    public function get() {        
        $this->restserver->response(array(
            'get' => $this->restserver->get('id')
        ));
    }
    
    public function post() {        
        $this->restserver->response(array(
            'post' => $this->restserver->post('id')
        ));
    }
    
    public function put() {
        $this->restserver->response(array(
            'put' => $this->restserver->put('id')
        ));
    }
    
    public function delete() {
        $this->restserver->response(array(
            'delete' => $this->restserver->delete('id')
        ));
    }
}

/* End of file exemple.php */
/* Location: ./application/controllers/exemple.php */

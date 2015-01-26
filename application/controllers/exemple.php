<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.7 (20150125)
 */
class Exemple extends Restserver_Controller {

    public function __construct() {
        parent::__construct();
        
        // Configuration
        $fields = array();
        
        // Configuration d'un champ métier
        $fields[] = new Restserver_field(array(
            'input' => 'lastname', // Nom entrant
            'alias' => 'user.lastname|famille.pere.nom', // Modélisation interne
            'rules' => 'required_post|alpha|min_length[2]|max_length[250]', // Les règles à appliquer
            'name' => 'Nom', // Nom du champ
            'comment' => // Documentation et exemples
                "Nom: Nom de famille".PHP_EOL.
                "Type: string (min 2, max 250 caractères)".PHP_EOL.
                "Requis: POST"
        ));
        
        // Applique la configuration
        $this->restserver->add_field($fields);
    }
    
    /**
     * Méthode POST
     */
    public function post() {
        // ---------- Exemple de récupération
        // Récupération du champ entrant
        $lastname = $this->restserver->input('lastname');
        
        // Récupération du champ modélisé
        $alias = $this->restserver->alias();
        
        // Espace de nom 1
        $lastname = $alias['user']['lastname'];
        
        // Espace de nom 2
        $lastname = $alias['famille']['pere']['nom'];
        
        // ---------- Réponse
        $response = array();
        $response['status'] = TRUE;
        $response['error'] = NULL;
        $response['value'] = array(
            'lastname' => $lastname
        );
        
        // Envoi la réponse avec le code HTTP 201 Created
        $this->restserver->response($response, 201);
    }
    
    /**
     * Méthode GET
     */
    public function get() {        
        $this->restserver->response();
    }
        
    /**
     * Méthode PUR
     */
    public function put() {
        $this->restserver->response();
    }
    
    /**
     * Méthode DELETE
     */
    public function delete() {
        $this->restserver->response();
    }
}

/* End of file exemple.php */
/* Location: ./application/controllers/exemple.php */

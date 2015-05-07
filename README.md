# restserver
REST Full Server for Codeigniter

## Config
/application/config/restserver.php:
```php
$config['restserver'] = array(
    'allow_methods' => array('GET', 'POST', 'PUT', 'DELETE'),
    'allow_headers' => array('authorization', 'key', 'content-type', 'x-requested-with'),
    'allow_credentials' => FALSE,
    'allow_origin' => FALSE,
    'force_https' => FALSE,
    'ajax_only' => FALSE,
    'auth_http' => FALSE,
    'log' => FALSE,
    'log_driver' => 'file',
    'log_path' => "",
    'log_extra' => FALSE
);
```

## Extends controller
/application/core/MY_Controller.php:
```php
class MY_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Optional authentification
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

require(APPPATH.'/libraries/Restserver/Restserver_Controller.php');
```

## Examples
/application/controllers/exemple.php:
```php
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
```
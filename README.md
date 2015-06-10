# Restserver
REST Full Server for Codeigniter 2 and Codeigniter 3

## Installation
### Step 1 Installation by Composer
#### Edit /composer.json
```json
{
    "require": {
        "maltyxx/restserver": "1.2.*"
    }
}
```
#### Run composer update
```shell
composer update
```

### Step 2 Configuration form_validation
```txt
https://github.com/maltyxx/form_validation
```

### Step 3 Creates files
```txt
/application/libraries/Restserver.php
```
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'/libraries/Restserver/Restserver.php');
```
```txt
/application/core/MY_Controller.php:
```
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

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

### Step 4 Configuration
/application/config/restserver.php:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

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
    'log_db_name' => '', // Database only
    'log_db_table' => '', // Database only
    'log_file_path' => '', // File only
    'log_file_name' => '', // File only
    'log_extra' => FALSE
);
```

## Examples
/application/controllers/Server.php:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Server extends Restserver_Controller {

    public function __construct() {
        parent::__construct();
        
        // Configuration
        $fields = array();
        
        // Configuration d'un champ métier
        $fields[] = new Restserver_field(array(
            'input' => 'lastname', // Nom entrant
            'alias' => 'user.lastname|famille.pere.nom', // Modélisation interne
            'label' => 'Nom', // Nom du champ
            'rules' => 'required_post|alpha|min_length[2]|max_length[250]', // Les règles à appliquer
            'comment' => // Documentation et exemples
                "Input: lastname".PHP_EOL.
                "Label: Nom de famille".PHP_EOL.
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
        $lastname = $this->restserver->post('lastname');
        
        // Récupération du champ modélisé
        $alias = $this->restserver->alias();
        
        // Espace de nom 1
        $lastname = $alias['user']['lastname'];
        
        // Espace de nom 2
        $lastname = $alias['famille']['pere']['nom'];
        
        // ---------- Réponse
        $response = $this->restserver->protocol();
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
     * Méthode PUT
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

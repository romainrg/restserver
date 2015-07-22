# Restserver
REST Full Server for Codeigniter 3

## Requirements

- PHP 5.4.x (Composer requirement)
- CodeIgniter 3.0.x

## Installation
### Step 1 Installation by Composer
#### Run composer
```shell
composer require maltyxx/restserver
```

### Step 2 Create files
Create controller file in `/application/core/MY_Controller.php`.
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct() {
        parent::__construct();
    }
}

require(APPPATH.'/third_party/restserver/core/Restserver_controller.php');
```

### Step 3 Configuration
Duplicate configuration file `./application/third_party/restserver/config/restserver.php` in `./application/config/restserver.php`.

### Step 4 Examples
Controller file is located in `./application/controllers/Server.php`.
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Server extends Restserver_Controller {

    public function __construct() {
        parent::__construct();
        
        $fields = [];
        
        // Configuration d'un champ
        $fields[] = new Restserver_field([
            // Nom entrant (requis)
            'input' => 'lastname',
            
            // Modélisation interne (option)
            'alias' => 'user.lastname|famille.pere.nom',
            
            // Nom du champ (option)
            'label' => 'Nom',
            
            // Les règles (option)
            'rules' => 'required_post|alpha|min_length[2]|max_length[250]',
            
            // Documentation (option)
            'comment' =>
                "Input: lastname".PHP_EOL.
                "Label: Nom de famille".PHP_EOL.
                "Type: string (min 2, max 250 caractères)".PHP_EOL.
                "Requis: POST"
        ]);

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

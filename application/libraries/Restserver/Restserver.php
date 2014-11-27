<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.4 (20141125)
 */
class Restserver {

    /**
     * Instance de Codeigniter
     * @var object $CI
     */
    protected $CI;
    
    /**
     * Version
     * @var string
     */
    protected $version = '1.0.4 (20141125)';

    /**
     * Configuration
     * @var array
     */
    protected $config = array(
        'allow_methods' => array(),
        'allow_headers' => array(),
        'allow_credentials' => FALSE,
        'force_https' => FALSE,
        'ajax_only' => FALSE,
        'auth_http' => FALSE,
        'cache' => FALSE,
        'log' => FALSE,
        'log_driver' => 'file',
        'log_path' => '',
        'log_extra' => FALSE
    );
    
    /**
     * Protocole
     * @var array
     */
    protected $protocol = array(
        'status' => FALSE,
        'error' => NULL,
        'value' => NULL
    );
    
    /**
     * Instance du controlleur
     * @var CI_Controller
     */
    protected $controller;
    
    /**
     * La méthode
     * @var string
     */
    protected $method;
    
    /**
     * L'URL
     * @var string
     */
    protected $url;
    
    /**
     * L'IP
     * @var string
     */
    protected $ip;
    
    /**
     * L'identifiant
     * @var string
     */
    protected $username;
    
    /**
     * Le mot de passe
     * @var string
     */
    protected $password;
    
    /**
     * Le token
     * @var string
     */
    protected $key;
    
    /**
     * Les en-têtes
     * @var string
     */
    protected $headers;
    
    /**
     * Les entrées
     * @var array
     */
    protected $input;
    
    /**
     * Les sorties
     * @var string
     */
    protected $output;
    
    /**
     * Le temps d'exécution
     * @var string
     */
    protected $exectime;
    
    /**
     * Configuration des champs
     * @var array
     */
    private $fields = array();
        
    /**
     * Les 
     * @var array
     */
    protected $field_input = array();
    
    /**
     *
     * @var type 
     */
    protected $alias = array();
            
    /**
     * Constructeur
     * @param array $config
     */
    function __construct(array $config = array()) {
        
        // Charge l'instance de CodeIgniter
        $this->CI =& get_instance();
        
        // Initialise la configuration, si elle existe
        if (isset($config['restserver']))
            $this->config = array_merge($this->config, $config['restserver']);
        
        // Si le journal est activé
        if ($this->config['log'])
            $this->CI->benchmark->mark('restserver_start');
        
        // Change les paquets
        $this->CI->load->library('orm');
        $this->CI->load->library('form_validation');
        $this->CI->load->helper('url');
    }
    
    /**
     * Exécute la routine
     * @param CI_Controller $controller
     * @param string $call
     * @param array $params
     * @return boolean
     */
    public function run(CI_Controller &$controller, $call, $params) {
        // Collecte les données
        $this->controller =& $controller;
        $this->method = $this->_get_method();
        $this->url = $this->_get_url();
        $this->ip = $this->_get_ip();
        $identifiants = $this->_get_username_password();
        $this->username = $identifiants['username'];
        $this->password = $identifiants['password'];
        $this->key = $this->_get_key();
        $this->headers = $this->_get_headers();
        $this->input = $this->_get_input();
        
        // Envoi les autorisations pour le cross-domain
        $this->_cross_domain();
        
        // Si la requête est de type option (cross domain)
        if ($this->method === 'option')
            return $this->response(array(
                'status' => TRUE
            ), 200);
        
        // Si le protocole SSL est obligatoire
        if ($this->config['force_https'] && ! $this->_is_sslprotocol()) {
			return $this->response(array(
                'status' => FALSE,
                'error' => 'Unsupported protocol'
            ), 403);
		}
        
        // Si la requête est en ajax
        if ($this->config['ajax_only'] && ! $this->CI->input->is_ajax_request()) {
			return $this->response(array(
                'status' => FALSE,
                'error' => 'Only AJAX requests are accepted'
            ), 505);
		}
        		
        // Authentification
        if ($this->_auth() === FALSE) {
            return $this->response(array(
                'status' => FALSE,
                'error' => 'Authorization failed'
            ), 401);
        }
                
        // Si la méthode existe
        if ( ! method_exists($this->controller, $this->method)) {
            return $this->response(array(
                'status' => FALSE,
                'error' => 'Method not found'
            ), 403);
        }
        
        // Si la documentation est demandé
        if (isset($this->input['get']['help'])) {
            // Récupère les fields pour la documentation
            $docs = $this->_get_docs();
            
            // Si il existe une docuementation
            if ( ! empty($docs)) {
                return $this->response(array(
                    'status' => TRUE,
                    'value' => $docs
                ), 200);
            }
        }
        
        // Récupère les règles
        $rules = $this->_get_rules();
        
        // Si des règles existent
        if ( ! empty($rules)) {
            // Vérification des données entrantes
            $this->CI->form_validation->set_data($this->input[$this->method]);
            $this->CI->form_validation->set_rules($rules);
            $this->CI->form_validation->set_error_delimiters('', '');

            // Si le validateur a rencontré une ou plusieurs erreurs
            if ($this->CI->form_validation->run() === FALSE) {
                return $this->response(array(
                    'status' => FALSE,
                    'error' => $this->CI->form_validation->get_errors()
                ), 403);
            }
        }
        
        // Création des input
        $this->field_input = $this->_get_field_input();
        
        // Création des alias
        $this->alias = $this->_get_alias();
        
        // Exécute la méthode
        try {
            call_user_func_array(array($this->controller, $this->method), $params);
        } catch (Exception $error) {
            $this->response(array(
                'status' => FALSE,
                'error' => $error
            ), 403);
        }
    }
    
    /**
     * Ajoute une configuration pour un champ
     */
    public function add_field(Restserver_field $field) {
        $this->fields[] = $field;
    }
    
    /**
     * Obtenir une ou plusieurs variables d'entrées
     * @param string|NULL $key
     * @return mixed
     */
    public function input($key = NULL) {
        if ($key !== NULL)
            return (isset($this->field_input[$key])) ? $this->field_input[$key] : FALSE;
        
        return $this->field_input;
    }
    
    /**
     * Obtenir une ou plusieurs alias
     * @param string|NULL $key
     * @param string $namespace
     * @return type
     */
    public function alias($key = NULL, $namespace = 'default') {
        if ($key !== NULL)
            return (isset($this->alias[$namespace][$key])) ? $this->alias[$namespace][$key] : FALSE;
        
        return ( ! empty($namespace)) ? $this->alias[$namespace] : $this->alias;
    }
        
    /**
     * Les données de la méthode Get
     * @param string|null $key
     * @param boolean $xss_clean
     * @return array|booblean
     */
    public function get($key = NULL, $xss_clean = TRUE) {
        return $this->_get_input_method('get', $key, $xss_clean);
    }
    
    /**
     * Les données de la méthode Post
     * @param string|null $key
     * @param boolean $xss_clean
     * @return array|booblean
     */
    public function post($key = NULL, $xss_clean = TRUE) {
        return $this->_get_input_method('post', $key, $xss_clean);
    }
    
    /**
     * Les données de la méthode Put
     * @param string|null $key
     * @param boolean $xss_clean
     * @return array|booblean
     */
    public function put($key = NULL, $xss_clean = TRUE) {
        return $this->_get_input_method('put', $key, $xss_clean);
    }
    
    /**
     * Les données de la méthode Delete
     * @param string|null $key
     * @param boolean $xss_clean
     * @return array|booblean
     */
    public function delete($key = NULL, $xss_clean = TRUE) {        
        return $this->_get_input_method('delete', $key, $xss_clean);
    }
    
    /**
     * Envoi une réponce au client
     * @param array $data
     * @param integer|null $code
     */
    public function response(array $data = array(), $code = NULL) {
        // Si il y a aucun data
        if (empty($data)) {
            $data = $this->protocol;
            $code = 404;
        }
        
        // Si il y a pas de code HTTP
        if (empty($code))
            $code = 200;
        
        // Format de sortie
        $this->CI->output->set_content_type('json');
                        
        // Définition du code HTTP
        $this->CI->output->set_status_header($code);
                        
        // Encode le data
        $this->CI->output->set_output(json_encode($data));
                
        // Si le journal est activé
        if ($this->config['log']) {
            // Termine le bench
            $this->CI->benchmark->mark('restserver_end');
            $this->exectime = $this->CI->benchmark->elapsed_time('restserver_start', 'restserver_end');
            
            $log_model = new \rest\log_model();
            $log_model->method = ( ! empty($this->method)) ? $this->method : NULL;
            $log_model->url = ( ! empty($this->url)) ? $this->url : NULL;
            $log_model->ip = ( ! empty($this->ip)) ? $this->ip : NULL;
            $log_model->user = ( ! empty($this->user)) ? $this->user : NULL;
            $log_model->password = ( ! empty($this->password)) ? $this->password : NULL;
            $log_model->key = ( ! empty($this->key)) ? $this->key : NULL;
            $log_model->exectime = $this->exectime;

            if ($this->config['log_extra']) {
                $this->output = $this->CI->output->get_output();
                
                $log_model->headers = ( ! empty($this->headers)) ? json_encode($this->headers) : NULL;
                $log_model->input = ( ! empty($this->input)) ? json_encode($this->input) : NULL;
                $log_model->output = ( ! empty($this->output)) ? $this->output : NULL;
            }

            $log_model->dateinsert = date('Y-m-d H:i:s');
            
            // Enregistre le journal
            $this->_set_log($log_model);
        }
    }
    
    /**
     * Retourne la version
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Les données entrantes
     * @param string|null $key
     * @param boolean $xss_clean
     * @return array|booblean
     */
    private function _get_input_method($method, $key = NULL, $xss_clean = TRUE) {                
        if ($key === NULL) {
            $input = array();
            
            foreach (array_keys($this->input[$method]) as $name) {
                $input[$name] = $this->CI->input->_fetch_from_array($this->input[$method], $name, $xss_clean);
            }
            
            return $input;
        }
        
        return $this->CI->input->_fetch_from_array($this->input[$method], $key, $xss_clean);
    }
    
    /**
     * Si le protocol est de type HTTPS
     * @return boolean
     */
    private function _is_sslprotocol() {
        return ($this->CI->input->server('HTTPS') == 'on');
    }
    
    /**
     * Envoi les entêtes pour le cross domaine
     */
    private function _cross_domain() {
        // Autorise le cross-domain
        $this->CI->output->set_header('Access-Control-Allow-Methods: '.implode(',', $this->config['allow_methods']));
        $this->CI->output->set_header('Access-Control-Allow-Headers: '.implode(',', $this->config['allow_headers']));
        
        if ($this->config['allow_credentials'])
            $this->CI->output->set_header('Access-Control-Allow-Credentials: true');
        
        $this->CI->output->set_header('Access-Control-Allow-Origin: '.(( ! empty($this->headers['Origin'])) ? $this->headers['Origin'] : $this->ip));
    }
    
    /**
     * Retourne la méthode
     * @return string la méthode
     */
    private function _get_method() {
        $method = $this->CI->input->server('REQUEST_METHOD');
        return ( ! empty($method)) ? strtolower($method) : '';
    }
    
    /**
     * Retourne l'URL
     * @return string
     */
    private function _get_url() {
        $url = current_url();
        return ( ! empty($url)) ? $url : '';
    }
    
    /**
     * Retourne l'adresse IP
     * @return string
     */
    private function _get_ip() {
        $ip = $this->CI->input->ip_address();
        return ( ! empty($ip)) ? $ip : '';
    }
    
    /**
     * Retourne le nom de l'utilisateur et le mot de passe
     * @return array
     */
    private function _get_username_password() {
        $username = $this->CI->input->server('PHP_AUTH_USER');
        $password = $this->CI->input->server('PHP_AUTH_PW');
        
        return array('username' => (string)$username, 'password' => (string)$password);
    }
            
    /**
     * Retourne la liste des en-têtes
     * @return array
     */
    private function _get_headers() {
        $headers = $headers = $this->CI->input->request_headers(TRUE);        
        return ( ! empty($headers)) ? $headers : array();
    }
    
    /**
     * Retourne toutes les données entrantes
     * @return array
     */
    private function _get_input() {
        $input = array(
            'get' => $this->CI->input->get(),
            'post' => $this->CI->input->post(),
            'put' => array(),
            'delete' => array()
        );
        
        // Récupère les autres flux entrant
        parse_str(file_get_contents('php://input'), $input['put']);
        parse_str(file_get_contents('php://input'), $input['delete']);
        
        // Si le flux est vide
        if ( ! is_array($input['get']))
            $input['get'] = array();
        
        // Si le flux est vide
        if ( ! is_array($input['post']))
            $input['post'] = array();
        
        return $input;
    }
    
    /**
     * Authentification
     * @return boolean
     */
    private function _auth() {
        // Si l'autentification par HTTP est activé et qu'il existe une surcharge
        if ($this->config['auth_http'] && method_exists($this->controller, '_auth_login')) {
            return call_user_func_array(array($this->controller, '_auth_login'), array(
                $this->username,
                $this->password,
                $this->ip
            ));
            
        // Si l'autentification par HTTP est activé et qu'il n'existe pas de surcharge
        } else if ($this->config['auth_http']) {
            return $this->_auth_login($this->username, $this->password, $this->ip);
        }
        
        return TRUE;
    }
        
    /**
     * Authentification par nom d'utilisateur et mot de passe
     * @param string $username
     * @param string $password
     * @param string $ip
     * @return boolean
     */
    private function _auth_login($username, $password, $ip) {
        // Si l'utilisateur est vide
        if (empty($username))
            return FALSE;
        
        // Intéroge la base de donnée
        $user_model = new \rest\user_model();
        $user = $user_model->where(array(
            'username' => $username,
            'password' => sha1($password),
            'status' => 1,
        ))->find_one();
        
        // Si l'identifiacation est correcte
        if ( ! empty($user)) {
            // Si il y a pas de restriction sur l'adresse IP
            if (empty($user->ip)) {
                return TRUE;
            
            // Si l'adresse IP est correcte
            } else if ( ! empty($user->ip) && $user->ip == $ip) {
                return TRUE;
            }
        }
        
        // Si l'identification est incorrecte
        return FALSE;
    }
        
    /**
     * Retourne les règles
     * @return array
     */
    private function _get_rules() {
        $rules = array();
        
        // Si le tableau des champs n'est pas vide
        if ( ! empty($this->fields)) {
            foreach ($this->fields as $field) {
                if ($field instanceof Restserver_field) {
                    // Récupération des règles
                    $rules[] = $field->get_rules();
                }
            }
        }
        
        return $rules;
    }
    
    /**
     * Création des alias
     * @return array
     */
    private function _get_alias() {
        $alias = array();
        
        // Si des champs existent
        if ( ! empty($this->fields)) {
            foreach ($this->fields as $field) {
                // Si se sont des objects de type Restserver_field
                if ($field instanceof Restserver_field) {
                    
                    // Si il y a plusieurs ojects
                    if (strstr($field->alias, '|') !== FALSE) {
                        $alias_array = explode('|', $field->alias);
                        
                    // Si il y a qu'un seul object
                    } else {
                        $alias_array = array($field->alias);
                    }
                    
                    foreach ($alias_array as $value) {
                        
                        // Si il n'y a pas d'espace de nom
                        if (strstr($value, '.') === FALSE)
                            $value = "default.$value";
                        
                        // Valeur de l'entrée
                        if ((isset($this->input[$this->method][$field->input]))) {
                            $input_value =& $this->input[$this->method][$field->input];
                        } else {
                            $input_value = NULL;
                        }
                        
                        // Création des espaces de nom
                        $alias = array_merge_recursive($alias, namespace_recursive(explode('.', $value), $input_value));
                    }
                }
            }
        }
        
        return $alias;
    }
    
    /**
     * Espace de nom récursif
     * @param array $spaces
     * @param mixe $value
     * @param array $return
     * @return array
     */
    private function _namespace_recursive(array $spaces, $value = NULL, array $return = array()) {
        $space = array_shift($spaces);

        $return[$space] = $return;

        if ( ! empty($spaces)) {
            $return[$space] = $this->_namespace_recursive($spaces, $value, $return[$space]);
        } else  {
            $return[$space] =& $value;
        }

        return $return;
    }
    
    /**
     * Création des champs d'entrée
     * @return array
     */
    private function _get_field_input() {
        $input = array();
        
        // Si des champs existent
        if ( ! empty($this->fields)) {
            foreach ($this->fields as $field) {
                
                // Si se sont des objects de type Restserver_field
                if ($field instanceof Restserver_field) {
                    
                    // Si la donnée d'entrée existe
                    if (isset($this->input[$this->method][$field->input])) {
                        $input[$field->input] =& $this->input[$this->method][$field->input];
                    
                    // Si elle n'existe pas ça valeur est NULL
                    } else {
                        $input[$field->input] = NULL;
                    }
                }
            }
        }
        
        return $input;
    }
    
    /**
     * Retourne les documentations
     * @return array
     */
    private function _get_docs() {
        $docs = array();
        
        // Si le tableau des champs n'est pas vide
        if ( ! empty($this->fields)) {
            foreach ($this->fields as $field) {
                if ($field instanceof Restserver_field) {
                    $docs[$field->input] = $field->comment;
                }
            }
        }
        
        return $docs;
    }

    /**
     * Insert les évènements dans un journal
     * @param \rest\log_model $log_model
     */
    private function _set_log(\rest\log_model $log_model) {      
        switch ($this->config['log_driver']) {            
            case 'database':
                $log_model->save();
                break;
            case 'file':
            default:
                $file_name = 'restserver.log';
                $file_path = ( ! empty($this->config['log_path'])) ? $this->config['log_path'] : sys_get_temp_dir();
                $file = "$file_path/$file_name";

                if (touch($file)) {
                    if (is_file($file) && is_writable($file)) {
                        $log = "method: $log_model->methode".PHP_EOL;
                        $log .= " url: $log_model->url".PHP_EOL;
                        $log .= " ip: $log_model->ip".PHP_EOL;
                        $log .= " user: $log_model->user".PHP_EOL;
                        $log .= " password: $log_model->password".PHP_EOL;
                        $log .= " key: $log_model->key".PHP_EOL;

                        if ($this->config['log_extra']) {
                            $log .= " headers: $log_model->headers".PHP_EOL;
                            $log .= " input: $log_model->input".PHP_EOL;
                            $log .= " output: $log_model->output".PHP_EOL;
                        }

                        $log .= " exectime: $log_model->exectime".PHP_EOL;
                        $log .= " date: $log_model->dateinsert".PHP_EOL;

                        error_log($log, 3, $file);
                    }
                }
        }
    }
}

/* End of file Restserver.php */
/* Location: ./application/libraries/Restserver/Restserver.php */
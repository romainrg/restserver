<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link https://github.com/maltyxx/restserver
 */
class Restserver_field {
    
    public $input;
    public $alias;
    public $label;
    public $rules;
    public $errors;
    public $comment;
    
    public function __construct(array $config) {
        foreach ($config as $config_key => $config_value) {
            $this->{$config_key} = $config_value;
        }
        
        // Si le name est définie
        if (!empty($config['name']))
            $this->label = $config['name'];
        
        // Si l'alias n'est pas définie
        if (empty($this->alias))
            $this->alias = $this->input;
        
        // Si le label n'est pas définie
        if (empty($this->label))
            $this->label = $this->input;
        
        
    }
    
    public function get_rules() {
        return array(
            'field' => $this->input,
            'label' => $this->label,
            'rules' => $this->rules,
            'errors' => $this->errors
        );
    }
    
}

/* End of file Restserver_field.php */
/* Location: ./application/libraries/Restserver/Restserver_field.php */

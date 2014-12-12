<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.5 (20141212)
 */
class Restserver_field {
    
    public $input;
    public $alias;
    public $name;
    public $rules;
    public $comment;
    
    public function __construct(array $config) {
        foreach ($config as $config_key => $config_value) {
            $this->{$config_key} = $config_value;
        }
        
        // Si l'alias n'est pas définie
        if (empty($this->alias))
            $this->alias = $this->input;
        
        // Si le name n'est pas définie
        if (empty($this->name))
            $this->name = $this->input;
    }
    
    public function get_rules() {
        return array(
            'field' => $this->input,
            'label' => $this->name,
            'rules' => $this->rules
        );
    }
    
}

/* End of file Restserver_field.php */
/* Location: ./application/libraries/Restserver/Restserver_field.php */

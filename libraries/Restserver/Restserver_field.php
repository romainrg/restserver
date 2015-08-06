<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link https://github.com/maltyxx/restserver
 */
class Restserver_field
{
    /**
     * Nom de la donnée entrante
     * @var string 
     */
    public $input;

    /**
     * Les alias
     * @var string 
     */
    public $alias;

    /**
     * Nom du label
     * @var type
     */
    public $label;

    /**
     * Les règles
     * @var string
     */
    public $rules;

    /**
     * Les erreurs
     * @var type 
     */
    public $errors;

    /**
     * Commentaire
     * @var string
     */
    public $comment;

    /**
     * Constructeur
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $config_key => $config_value) {
            $this->{$config_key} = $config_value;
        }

        // Si le name est définie
        if (!empty($config['name'])) {
            $this->label = $config['name'];
        }

        // Si l'alias n'est pas définie
        if (empty($this->alias)) {
            $this->alias = $this->input;
        }

        // Si le label n'est pas définie
        if (empty($this->label)) {
            $this->label = $this->input;
        }
    }

    /**
     * Retourne la configuration pour le form_validator
     * @return array|boolean
     */
    public function getRules()
    {
        if (empty($this->rules)) {
            return NULL;
        }

        return [
            'field' => $this->input,
            'label' => $this->label,
            'rules' => $this->rules,
            'errors' => $this->errors
        ];
    }
}

/* End of file Restserver_field.php */
/* Location: ./libraries/Restserver/Restserver_field.php */

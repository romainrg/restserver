<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.6 (20150112)
 */
class MY_Form_validation extends CI_Form_validation {

    private $method;
    private $data = array();
    private $errors = array();

    /**
     * Constructor
     */
    public function __construct($rules = array()) {        
        // Envoi les règles à la classe parente
        parent::__construct($rules);
        
        // Réception des données entrantes
        $this->set_data();
    }
    
    /**
     * Définit les données entrante a vérifier
     * @param mixe $data
     */
    public function set_data($data = NULL) {
        // Récupère la méthode de la requête
        $method = $this->CI->input->server('REQUEST_METHOD');
       
        // La méthode
        $this->method = ( ! empty($method)) ? strtolower($method) : 'post';
        
        // Les données
        $this->data = ( ! is_null($data)) ? $data: $this->CI->input->post();

    }

    /**
     * Get Errors
     *
     * Retourne les erreurs pour chaques champs
     *
     * @return array
     */
    public function get_errors() {
        $errors = array();
        
        foreach ($this->_field_data as $value) {
            if ($this->error($value['field']) != '')
                $errors[$value['field']] = $this->error($value['field']);
        }

        return $errors;
    }

    /**
     * Set Rules
     *
     * This function takes an array of field names and validation
     * rules as input, validates the info, and stores it
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @return	void
     */
    public function set_rules($field, $label = '', $rules = '') {

        // No reason to set rules if we have no POST data
        if (count($this->data) == 0)
            return $this;

        // If an array was passed via the first parameter instead of indidual string
        // values we cycle through it and recursively call this function.
        if (is_array($field)) {
            foreach ($field as $row) {
                // Houston, we have a problem...
                if (!isset($row['field']) OR ! isset($row['rules']))
                    continue;

                // If the field label wasn't passed we use the field name
                $label = (!isset($row['label'])) ? $row['field'] : $row['label'];

                // Here we go!
                $this->set_rules($row['field'], $label, $row['rules']);
            }
            return $this;
        }

        // No fields? Nothing to do...
        if (!is_string($field) OR ! is_string($rules) OR $field == '')
            return $this;

        // If the field label wasn't passed we use the field name
        $label = ($label == '') ? $field : $label;

        // Is the field name an array?  We test for the existence of a bracket "[" in
        // the field name to determine this.  If it is an array, we break it apart
        // into its components so that we can fetch the corresponding POST data later
        if (strpos($field, '[') !== FALSE AND preg_match_all('/\[(.*?)\]/', $field, $matches)) {
            // Note: Due to a bug in current() that affects some versions
            // of PHP we can not pass function call directly into it
            $x = explode('[', $field);
            $indexes[] = current($x);

            for ($i = 0; $i < count($matches['0']); $i++) {
                if ($matches['1'][$i] != '')
                    $indexes[] = $matches['1'][$i];
            }

            $is_array = TRUE;
        } else {
            $indexes = array();
            $is_array = FALSE;
        }

        // Build our master array
        $this->_field_data[$field] = array(
            'field' => $field,
            'label' => $label,
            'rules' => $rules,
            'is_array' => $is_array,
            'keys' => $indexes,
            'postdata' => NULL,
            'error' => ''
        );

        return $this;
    }

    /**
     * Run the Validator
     *
     * This function does all the work.
     *
     * @access	public
     * @return	bool
     */
    public function run($group = '') {
        // Do we even have any data to process?  Mm?
        if (count($this->data) == 0)
            return FALSE;

        // Does the _field_data array containing the validation rules exist?
        // If not, we look to see if they were assigned via a config file
        if (count($this->_field_data) == 0) {
            // No validation rules?  We're done...
            if (count($this->_config_rules) == 0)
                return FALSE;

            // Is there a validation rule for the particular URI being accessed?
            $uri = ($group == '') ? trim($this->CI->uri->ruri_string(), '/') : $group;

            if ($uri != '' AND isset($this->_config_rules[$uri])) {
                $this->set_rules($this->_config_rules[$uri]);
            } else {
                $this->set_rules($this->_config_rules);
            }

            // We're we able to set the rules correctly?
            if (count($this->_field_data) == 0) {
                log_message('debug', "Unable to find validation rules");
                return FALSE;
            }
        }

        // Load the language file containing error messages
        $this->CI->lang->load('form_validation');

        // Cycle through the rules for each field, match the
        // corresponding $_POST item and test for errors
        foreach ($this->_field_data as $field => $row) {
            // Fetch the data from the corresponding $_POST array and cache it in the _field_data array.
            // Depending on whether the field name is an array or a string will determine where we get it from.

            if ($row['is_array'] == TRUE) {
                $this->_field_data[$field]['postdata'] = $this->_reduce_array($this->data, $row['keys']);
            } else {
                if (isset($this->data[$field]) AND $this->data[$field] != "")
                    $this->_field_data[$field]['postdata'] = $this->data[$field];
            }

            $this->_execute($row, explode('|', $row['rules']), $this->_field_data[$field]['postdata']);
        }

        // Did we end up with any errors?
        $total_errors = count($this->_error_array);

        if ($total_errors > 0)
            $this->_safe_form_data = TRUE;

        // Now we need to re-set the POST data with the new, processed data
        $this->_reset_post_array();

        // No errors, validation passes!
        if ($total_errors == 0)
            return TRUE;

        // Validation fails
        return FALSE;
    }

    /**
     * Re-populate the _POST array with our finalized and processed data
     *
     * @access	private
     * @return	null
     */
    protected function _reset_post_array() {
        foreach ($this->_field_data as $field => $row) {
            if (!is_null($row['postdata'])) {
                if ($row['is_array'] == FALSE) {
                    if (isset($this->data[$row['field']]))
                        $this->data[$row['field']] = $this->prep_for_form($row['postdata']);
                } else {
                    // start with a reference
                    $post_ref = & $this->data;

                    // before we assign values, make a reference to the right POST key
                    if (count($row['keys']) == 1) {
                        $post_ref = & $post_ref[current($row['keys'])];
                    } else {
                        foreach ($row['keys'] as $val) {
                            $post_ref = & $post_ref[$val];
                        }
                    }

                    if (is_array($row['postdata'])) {
                        $array = array();
                        foreach ($row['postdata'] as $k => $v) {
                            $array[$k] = $this->prep_for_form($v);
                        }

                        $post_ref = $array;
                    } else {
                        $post_ref = $this->prep_for_form($row['postdata']);
                    }
                }
            }
        }
    }

    /**
     * Executes the Validation routines
     *
     * @access	private
     * @param	array
     * @param	array
     * @param	mixed
     * @param	integer
     * @return	mixed
     */
    protected function _execute($row, $rules, $postdata = NULL, $cycles = 0) {
        // If the $_POST data is an array we will run a recursive call
        if (is_array($postdata)) {
            foreach ($postdata as $key => $val) {
                $this->_execute($row, $rules, $val, $cycles);
                $cycles++;
            }

            return;
        }

        // If the field is blank, but NOT required, no further tests are necessary
        $callback = FALSE;
        if (!in_array('required', $rules) AND is_null($postdata)) {
            // Before we bail out, does the rule contain a callback?
            if (preg_match("/(callback_\w+(\[.*?\])?)/", implode(' ', $rules), $match)) {
                $callback = TRUE;
                $rules = (array('1' => $match[1]));
            } else {
                return;
            }
        }

        // Isset Test. Typically this rule will only apply to checkboxes.
        if (is_null($postdata) AND $callback == FALSE) {
            if (in_array('isset', $rules, TRUE) OR in_array('required', $rules)) {
                // Set the message type
                $type = (in_array('required', $rules)) ? 'required' : 'isset';

                if (!isset($this->_error_messages[$type])) {
                    if (FALSE === ($line = $this->CI->lang->line($type)))
                        $line = 'The field was not set';
                } else {
                    $line = $this->_error_messages[$type];
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']));

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']]))
                    $this->_error_array[$row['field']] = $message;
            }

            return;
        }

        // Cycle through each rule and run it
        foreach ($rules As $rule) {
            $_in_array = FALSE;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] == TRUE AND is_array($this->_field_data[$row['field']]['postdata'])) {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if (!isset($this->_field_data[$row['field']]['postdata'][$cycles]))
                    continue;

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = TRUE;
            } else {
                $postdata = $this->_field_data[$row['field']]['postdata'];
            }

            // Is the rule a callback?
            $callback = FALSE;
            if (substr($rule, 0, 9) == 'callback_') {
                $rule = substr($rule, 9);
                $callback = TRUE;
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = FALSE;
            if (preg_match("/(.*?)\[(.*)\]/", $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
            }

            // Call the function that corresponds to the rule
            if ($callback === TRUE) {
                if (!method_exists($this->CI, $rule))
                    continue;

                // Run the function and grab the result
                $result = $this->CI->$rule($postdata, $param);

                // Re-assign the result to the master data array
                if ($_in_array == TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }

                // If the field isn't required and we just processed a callback we'll move on...
                if (!in_array('required', $rules, TRUE) AND $result !== FALSE)
                    continue;
            } else {
                if (!method_exists($this, $rule)) {
                    // If our own wrapper function doesn't exist we see if a native PHP function does.
                    // Users can use any native PHP function call that has one param.
                    if (function_exists($rule)) {
                        $result = $rule($postdata);

                        if ($_in_array == TRUE) {
                            $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                        } else {
                            $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                        }
                    } else {
                        log_message('debug', "Unable to find validation rule: ".$rule);
                    }

                    continue;
                }

                $result = $this->$rule($postdata, $param);

                if ($_in_array == TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }
            }

            // Did the rule test negatively?  If so, grab the error.
            if ($result === FALSE) {
                if (!isset($this->_error_messages[$rule])) {
                    if (FALSE === ($line = $this->CI->lang->line($rule)))
                        $line = 'Unable to access an error message corresponding to your field name.';
                } else {
                    $line = $this->_error_messages[$rule];
                }

                // Is the parameter we are inserting into the error message the name
                // of another field?  If so we need to grab its "field label"
                if (isset($this->_field_data[$param]) AND isset($this->_field_data[$param]['label']))
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']), $param);

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']]))
                    $this->_error_array[$row['field']] = $message;

                return;
            }
        }
    }

    /**
     * Match one field to another
     *
     * @access	public
     * @param	string
     * @param	field
     * @return	bool
     */
    public function matches($str, $field) {
        if (!isset($this->data[$field]))
            return FALSE;

        $field = $this->data[$field];

        return ($str !== $field) ? FALSE : TRUE;
    }
    
    /**
     * Le champ est obligatoire pour la méthode POST
     * @param mixe $str
     * @return boolean
     */
    public function required_post($str) {
        if ($this->method === 'post')
            return ($this->required($str));
        
        return TRUE;
    }
    
    /**
     * Le champ est obligatoire pour la méthode GET
     * @param mixe $str
     * @return boolean
     */
    public function required_get($str) {
        if ($this->method === 'get')
            return ($this->required($str));
        
        return TRUE;
    }
    
    /**
     * Le champ est obligatoire pour la méthode PUT
     * @param mixe $str
     * @return boolean
     */
    public function required_put($str) {
        if ($this->method === 'put')
            return ($this->required($str));
        
        return TRUE;
    }
    
    /**
     * Le champ est obligatoire pour la méthode DELETE
     * @param mixe $str
     * @return boolean
     */
    public function required_delete($str) {
        if ($this->method === 'delete')
            return ($this->required($str));
        
        return TRUE;
    }
}

/* End of file MY_Form_validation.php */
/* Location: ./application/libraries/MY_Form_validation.php */
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Form Validation Class
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Validation
 * @author      EllisLab Dev Team
 * @link        http://codeigniter.com/user_guide/libraries/form_validation.html
 */
class MY_Form_validation extends CI_Form_validation {
    /**
     * Request method
     * 
     * @var string 
     */
    public $request_method = '';

    /**
     * Required list
     * 
     * @var array 
     */
    public $required = array(
        'required',
        'required_post',
        'required_get',
        'required_put',
        'required_delete'
    );

    // --------------------------------------------------------------------

    /**
     * Set Rules
     *
     * This function takes an array of field names and validation
     * rules as input, any custom error messages, validates the info,
     * and stores it
     *
     * @param	mixed	$field
     * @param	string	$label
     * @param	mixed	$rules
     * @param	array	$errors
     * @return	CI_Form_validation
     */
    public function set_rules($field, $label = '', $rules = array(), $errors = array()) {
        $this->request_method = $this->CI->input->method();
        return parent::set_rules($field, $label, $rules, $errors);
    }
    
    // --------------------------------------------------------------------

    /**
     * Executes the Validation routines
     *
     * @param	array
     * @param	array
     * @param	mixed
     * @param	int
     * @return	mixed
     */
    protected function _execute($row, $rules, $postdata = NULL, $cycles = 0) {
        // If the $_POST data is an array we will run a recursive call
        if (is_array($postdata)) {
            foreach ($postdata as $key => $val) {
                $this->_execute($row, $rules, $val, $key);
            }

            return;
        }

        // If the field is blank, but NOT required, no further tests are necessary
        $callback = FALSE;
        if (!$this->_is_required($rules) && ($postdata === NULL OR $postdata === '')) {
            // Before we bail out, does the rule contain a callback?
            foreach ($rules as &$rule) {
                if (is_string($rule)) {
                    if (strncmp($rule, 'callback_', 9) === 0) {
                        $callback = TRUE;
                        $rules = array(1 => $rule);
                        break;
                    }
                } elseif (is_callable($rule)) {
                    $callback = TRUE;
                    $rules = array(1 => $rule);
                    break;
                }
            }

            if (!$callback) {
                return;
            }
        }

        // Isset Test. Typically this rule will only apply to checkboxes.
        if (($postdata === NULL OR $postdata === '') && !$callback) {
            if (in_array('isset', $rules, TRUE) OR $this->_is_required($rules)) {
                // Set the message type
                $type = (($rule = $this->_is_required($rules, TRUE)) !== FALSE) ? $rule : 'isset';

                // if required is not equale at request method
                if (strstr($type, 'required_') && $type != "required_$this->request_method") {
                    return;
                }

                // Check if a custom message is defined
                if (isset($this->_field_data[$row['field']]['errors'][$type])) {
                    $line = $this->_field_data[$row['field']]['errors'][$type];
                } elseif (isset($this->_error_messages[$type])) {
                    $line = $this->_error_messages[$type];
                } elseif (FALSE === ($line = $this->CI->lang->line('form_validation_'.$type))
                    // DEPRECATED support for non-prefixed keys
                    && FALSE === ($line = $this->CI->lang->line($type, FALSE))) {
                    $line = 'The field was not set';
                }

                // Build the error message
                $message = $this->_build_error_msg($line, $this->_translate_fieldname($row['label']));

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }
            }

            return;
        }

        // --------------------------------------------------------------------
        // Cycle through each rule and run it
        foreach ($rules as $rule) {
            $_in_array = FALSE;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] === TRUE && is_array($this->_field_data[$row['field']]['postdata'])) {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if (!isset($this->_field_data[$row['field']]['postdata'][$cycles])) {
                    continue;
                }

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = TRUE;
            } else {
                // If we get an array field, but it's not expected - then it is most likely
                // somebody messing with the form on the client side, so we'll just consider
                // it an empty field
                $postdata = is_array($this->_field_data[$row['field']]['postdata']) ? NULL : $this->_field_data[$row['field']]['postdata'];
            }

            // Is the rule a callback?
            $callback = $callable = FALSE;
            if (is_string($rule)) {
                if (strpos($rule, 'callback_') === 0) {
                    $rule = substr($rule, 9);
                    $callback = TRUE;
                }
            } elseif (is_callable($rule)) {
                $callable = TRUE;
            } elseif (is_array($rule) && isset($rule[0], $rule[1]) && is_callable($rule[1])) {
                // We have a "named" callable, so save the name
                $callable = $rule[0];
                $rule = $rule[1];
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = FALSE;
            if (!$callable && preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
            }

            // Call the function that corresponds to the rule
            if ($callback OR $callable !== FALSE) {
                if ($callback) {
                    if (!method_exists($this->CI, $rule)) {
                        log_message('debug', 'Unable to find callback validation rule: '.$rule);
                        $result = FALSE;
                    } else {
                        // Run the function and grab the result
                        $result = $this->CI->$rule($postdata, $param);
                    }
                } else {
                    $result = is_array($rule) ? $rule[0]->{$rule[1]}($postdata) : $rule($postdata);

                    // Is $callable set to a rule name?
                    if ($callable !== FALSE) {
                        $rule = $callable;
                    }
                }

                // Re-assign the result to the master data array
                if ($_in_array === TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                }

                // If the field isn't required and we just processed a callback we'll move on...
                if (!$this->_is_required($rules) && $result !== FALSE) {
                    continue;
                }
            } elseif (!method_exists($this, $rule)) {
                // If our own wrapper function doesn't exist we see if a native PHP function does.
                // Users can use any native PHP function call that has one param.
                if (function_exists($rule)) {
                    // Native PHP functions issue warnings if you pass them more parameters than they use
                    $result = ($param !== FALSE) ? $rule($postdata, $param) : $rule($postdata);

                    if ($_in_array === TRUE) {
                        $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                    } else {
                        $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                    }
                } else {
                    log_message('debug', 'Unable to find validation rule: '.$rule);
                    $result = FALSE;
                }
            } else {
                $result = $this->$rule($postdata, $param);

                if ($_in_array === TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                }
            }

            // Did the rule test negatively? If so, grab the error.
            if ($result === FALSE) {
                // Callable rules might not have named error messages
                if (!is_string($rule)) {
                    return;
                }

                // Check if a custom message is defined
                if (isset($this->_field_data[$row['field']]['errors'][$rule])) {
                    $line = $this->_field_data[$row['field']]['errors'][$rule];
                } elseif (!isset($this->_error_messages[$rule])) {
                    if (FALSE === ($line = $this->CI->lang->line('form_validation_'.$rule))
                        // DEPRECATED support for non-prefixed keys
                        && FALSE === ($line = $this->CI->lang->line($rule, FALSE))) {
                        $line = $this->CI->lang->line('form_validation_error_message_not_set').'('.$rule.')';
                    }
                } else {
                    $line = $this->_error_messages[$rule];
                }

                // Is the parameter we are inserting into the error message the name
                // of another field? If so we need to grab its "field label"
                if (isset($this->_field_data[$param], $this->_field_data[$param]['label'])) {
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);
                }

                // Build the error message
                $message = $this->_build_error_msg($line, $this->_translate_fieldname($row['label']), $param);

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }

                return;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Le champ est obligatoire pour la méthode POST
     * @param mixe $str
     * @return boolean
     */
    public function required_post($str) {
        if ($this->request_method === 'post') {
            return ($this->required($str));
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Le champ est obligatoire pour la méthode GET
     * @param mixe $str
     * @return boolean
     */
    public function required_get($str) {
        if ($this->request_method === 'get') {
            return ($this->required($str));
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Le champ est obligatoire pour la méthode PUT
     * @param mixe $str
     * @return boolean
     */
    public function required_put($str) {
        if ($this->request_method === 'put') {
            return ($this->required($str));
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Le champ est obligatoire pour la méthode DELETE
     * @param mixe $str
     * @return boolean
     */
    public function required_delete($str) {
        if ($this->request_method === 'delete') {
            return ($this->required($str));
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Reset validation vars
     *
     * Prevents subsequent validation routines from being affected by the
     * results of any previous validation routine due to the CI singleton.
     *
     * @return	CI_Form_validation
     */
    public function reset_validation() {
        $this->error_string = '';
        $this->request_method = '';
        return parent::reset_validation();
    }

    // --------------------------------------------------------------------

    /**
     * Is required
     * @param array $rules
     * @param boolean $return_filter
     * @return boolean
     */
    private function _is_required($rules, $return_filter = FALSE) {
        foreach ($rules as $rule) {
            if (in_array($rule, $this->required, TRUE)) {
                return ($return_filter) ? $rule : TRUE;
            }
        }

        return FALSE;
    }

    // --------------------------------------------------------------------
}

/* End of file MY_Form_validation.php */
/* Location: ./libraries/MY_Form_validation.php */
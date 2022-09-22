<?php

/**
 * Ajax Handler
 * Author: Dan's Art
 * Version: 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class DaAjaxHandler
{
    protected $success = []; //The success messages
    protected $error = []; //The error messages
    protected $system_error = []; //The system error messages

    /**
     * Returns the type of the ajax request
     *
     * @return void
     */
    public function get_ajax_type()
    {
        return $this->get_ajax_data('type');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function get_ajax_do()
    {
        return $this->get_ajax_data('do');
    }

    /**
     * Loads the data form the given field
     *
     * @param string $field - The name of the field
     * @return string The Value of the field
     */
    public function get_ajax_data(string $field = '')
    {
        return (isset($_REQUEST[$field])) ? $_REQUEST[$field] : "";
    }

    public function get_ajax_files_data(string $field = '')
    {
        return (isset($_FILES[$field]['name']) and !empty($_FILES[$field]['name'])) ? $_FILES[$field] : "";
    }

    /**
     * Returns the field value as an array.
     * Make sure that the value is a valid json string
     *
     * @param string $field - The fieldname
     * @return array The array
     */
    public function get_ajax_data_as_array(string $field = '', bool $escape = false)
    {
        $value = (isset($_REQUEST[$field])) ? stripslashes($_REQUEST[$field]) : "";
        $val_arr = json_decode($value);
        if ($escape and is_array($val_arr)) {
            //Escape all the data
            foreach ($val_arr as $index => $val) {
                $val_arr[$index] = htmlspecialchars($val);
            }
        }
        return is_array($val_arr) ? $val_arr : [$val_arr]; //Convert to array if no array
    }



    /**
     * Returns the Value from a $_Request field and applies htmlspecialchars() function 
     */
    public function get_ajax_data_esc(string $field = '', $remove_unallowed_tags = false)
    {
        if (isset($_REQUEST[$field]) and is_string($_REQUEST[$field])) {
            return ($remove_unallowed_tags) ? htmlspecialchars($_REQUEST[$field]) : htmlspecialchars($_REQUEST[$field]);
        }
        if (isset($_REQUEST[$field]) and is_array($_REQUEST[$field])) {
            $new_arr = array();
            foreach ($_REQUEST[$field] as $id => $value) {
                $new_arr[htmlspecialchars($id)] = htmlspecialchars($value);
            }
            return $new_arr;
        }
        return '';
    }

    /**
     * Get all the data from $_REQUEST.
     * If $ignore_defaults is set, the keys "do" and "action" will be removed.
     *
     * @param boolean $ignore_defaults - Ignores the "do" and "action" fields
     * @return array $_REQUEST array
     */
    public function get_all_ajax_data(bool $ignore_defaults = true)
    {
        $data = $_REQUEST;
        if ($ignore_defaults) {
            unset($data['do']);
            unset($data['action']);
        }
        return $data;
    }

    public function set_error(string $message, string $field = "")
    {
        if (!empty($field)) {
            $this->error[$field][] = $message;
            return;
        }
        $this->error[] = $message;
        return;
    }

    public function set_error_array(array $errors)
    {
        if (empty($this->error)) {
            $this->error = $errors;
        } else {
            $this->error = array_merge($this->error, $errors);
        }
        return;
    }

    public function set_system_error(string $message)
    {
        $this->system_error[] = $message;
        return;
    }
    /**
     * Sets an Success message
     *
     * @param mixed $message
     * @return void
     */
    public function set_success($message)
    {
        $this->success[] = $message;
        return;
    }

    /**
     * Get the Errors from the global $plek_ajax_errors
     * Adds the errors to the $error variable
     * @return void
     */
    public function get_ajax_errors()
    {
        return $this->error;
    }
    
    protected function get_ajax_return()
    {
        $this->get_ajax_errors();
        $ret = ['success' => $this->success, 'error' => $this->error, 'system_error' => $this->system_error];
        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }
}

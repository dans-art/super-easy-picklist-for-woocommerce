<?php

/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * Class description: Functions called by Ajax requests 
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 *
 */

class SepAjax
{
    /**
     * All the order functions callable by ajax request
     *
     * @return void
     */
    public function sep_order_functions()
    {
        $order = $this->get_ajax_data('order');
        $order_object = SepOrder::load_order($order);
        echo json_encode($order_object);
        die();
    }

    /**
     * Function for logged out users. Since there are no actions for the unregistered user, it just returns a error.
     *
     * @return void
     */
    public function sep_order_functions_nopriv()
    {
        echo __('Nice try! You are not allowed to use this function!','sep');
        die();
    }

    /**
     * 
     *
     * @param [type] $key
     * @return void
     */
    public function get_ajax_data($key){
        return (!empty($_REQUEST[$key])) ? $_REQUEST[$key] : '';
    }
}

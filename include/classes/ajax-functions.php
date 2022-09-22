<?php

/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * Class description: Functions called by Ajax requests 
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 *
 */

class SepAjax extends DaAjaxHandler
{
    public function sep_ajax_orders()
    {
        $this->set_success(array(
            ['order_number' => '1', 'name' => 'Helmuth', 'date' => '12.06.2022', 'amount' => '5854'],
            ['order_number' => '2', 'name' => 'Edgar', 'date' => '14.06.2022', 'amount' => '44'],
            ['order_number' => '3', 'name' => 'Doris', 'date' => '15.06.2022', 'amount' => '5'],
        ));
        echo $this->get_ajax_return();
        die(); 
    }
    /**
     * Response for all the functions which need to have a logged in user.
     *
     * @return void
     */
    public function sep_ajax_nopriv_all()
    {
        $this->set_error(__('You have to be logged in to use those functions','sep'));
        echo $this->get_ajax_return();
        die();
    }
}

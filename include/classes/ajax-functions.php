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
    /**
     * The ajax order functions
     *
     * @return void
     */
    public function sep_ajax_orders()
    {
        $do = $this->get_ajax_do();
        $search = $this->get_ajax_data('search');
        $order_handler = new SepOrder;
        switch ($do) {
            case 'get_orders':
                $orders = $order_handler->get_orders($search);
                if($orders){
                    $this->set_success($orders);
                }
                else{
                    $this->set_error(__('No orders found','sep'));
                }
                break;
            
            default:
                # code...
                break;
        }
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

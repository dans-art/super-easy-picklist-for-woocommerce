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
        $order_handler = new SepOrder;
        switch ($do) {
            case 'add_tracking_code':
                $order_id = $this->get_ajax_data('order_id');
                $barcode = $this->get_ajax_data('barcode');
                $provider = $this->get_ajax_data('service_provider');
                $add_tracking = $order_handler->add_tracking_data($order_id, $barcode, $provider);
                if (!$add_tracking) {
                    $this->set_error(__('Tracking code could not be added to the order.', 'sep'));
                }
                break;
            case 'get_orders':
                $search = $this->get_ajax_data('search');
                $orders = $order_handler->get_orders($search);
                if ($orders) {
                    $this->set_success($orders);
                } else {
                    $this->set_error(__('No orders found', 'sep'));
                }
                break;
            case 'get_single_order':
                $order_id = $this->get_ajax_data('order_id');
                $order = $order_handler->get_single_order($order_id);
                if ($order) {
                    $this->set_success($order);
                } else {
                    $this->set_error(sprintf(__('No order with the ID %s found', 'sep'), $order_id));
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
        $this->set_error(__('You have to be logged in to use those functions', 'sep'));
        echo $this->get_ajax_return();
        die();
    }
}

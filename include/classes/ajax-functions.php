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
            /*case 'remove_tracking_code':
                $order_id = $this->get_ajax_data('order_id');
                $sp_id = $this->get_ajax_data('sp_id');
                $remove_tracking = $order_handler->remove_tracking_data($order_id, $sp_id);
                if ($remove_tracking !== true) {
                    $this->set_error(__('Tracking code could not be removed to the order:', 'sep') . $remove_tracking);
                }else{
                    $this->set_success(__('Tracking code removed from order','sep'));
                }
                break;
            case 'add_tracking_code':
                $order_id = $this->get_ajax_data('order_id');
                $barcode = $this->get_ajax_data('barcode');
                $provider = $this->get_ajax_data('service_provider');
                $add_tracking = $order_handler->add_tracking_data($order_id, $barcode, $provider);
                if ($add_tracking === false) {
                    $this->set_error(__('Tracking code could not be added to the order:', 'sep') . $add_tracking);
                }else{
                    $this->set_success(__('Tracking code added to order','sep'));
                }
                break;*/
            case 'pack_order':
                $order_id = $this->get_ajax_data('order_id');
                $sp_id = $this->get_ajax_data('sp_id');
                $sp_code = $this->get_ajax_data('sp_code');
                $items = $this->get_ajax_data('items');
                $packed = $order_handler->add_packed_order($order_id, $sp_id, $sp_code, $items);
                if ($packed === false) {
                    $this->set_error(__('Could not add the tracking code the order:', 'sep') . $packed);
                }else{
                    $this->set_success(__('Tracking code added to order','sep'));
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
            case 'update_status':
                $order_id = $this->get_ajax_data('order_id');
                $status = $this->get_ajax_data('status');
                $order = $order_handler->set_status($order_id, $status);
                if ($order === true) {
                    $this->set_success($order);
                } else {
                    $this->set_error(sprintf(__('Failed to update the order status: %s', 'sep'), $order));
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
     * The ajax settings functions
     *
     * @return void
     */
    public function sep_ajax_settings()
    {
        $do = $this->get_ajax_do();
        $settings_handler = new SepSettings;
        switch ($do) {
            case 'remove_shipping_provider':
                $sp_id = $this->get_ajax_data('id');
                if (empty($sp_id)) {
                    $this->set_error(__('Error: No ID provided', 'sep'), 'remove_tracking_code');
                    break;
                }
                $remove = $settings_handler->remove_shipping_provider($sp_id);
                if ($remove === false or $remove === null) {
                    $this->set_error(__('Error while removing shipping provider', 'sep'));
                } else {
                    $this->set_success(__('Service Provider removed', 'sep'));
                }
                break;
            case 'add_shipping_provider':
                $sp_name = $this->get_ajax_data('name');
                $sp_link = $this->get_ajax_data('link');
                if (empty($sp_name) or empty($sp_link)) {
                    $this->set_error(__('Error: No name or tracking link provided', 'sep'), 'add_tracking_code');
                    break;
                }
                $add = $settings_handler->save_new_shipping_provider($sp_name, $sp_link);
                if (!is_wp_error($add) and $add !== 0) {
                    $this->set_success($settings_handler->get_shipping_providers());
                } else {
                    $this->set_error(__('Error while adding a new shipping provider: ', 'sep') . $add->get_error_message());
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

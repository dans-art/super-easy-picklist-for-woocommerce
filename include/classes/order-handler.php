<?php

/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * Class description: Order methods
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SepOrder
{

    /**
     * Loads all the orders 
     *
     * @return void
     */
    public function get_orders($search = '', $limit = 10)
    {
        //Allow to add custom status for fetching the posts
        $status = apply_filters(
            'sep_order_status_to_get',
            array('wc-processing', 'wc-on-hold', 'wc-pending')
        );
        $args = array(
            'status' => $status,
            'limit' => $limit,
        );
        $orders = wc_get_orders($args);
        //Filter all the elements not needed.
        if (empty($orders)) {
            return false;
        }
        $orders = array_map(function ($order) {
            $order_data = $order->get_data();
            $items = $order -> get_items();

            if(is_array($items)){
                foreach($items as $index => $item){ //WC_Order_Item_Product 
                    $order_data['line_items'][$index] = $item->get_data();
                    $order_data['line_items'][$index]['sku'] = $item->get_product()->get_sku();
                }
            }
            return $order_data;
        }, $orders);
        return $orders;
    }
}

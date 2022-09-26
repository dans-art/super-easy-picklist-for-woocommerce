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
        $orders = wc_get_orders($args); //WC_Order
        //Filter all the elements not needed.
        if (empty($orders)) {
            return false;
        }
        $orders = $this->get_additional_order_data($orders);
        return $orders;
    }
    /**
     * Loads a single order
     *
     * @return void
     */
    public function get_single_order($id = '')
    {
        $order = wc_get_order($id); //WC_Order
        //Filter all the elements not needed.
        if (empty($order)) {
            return false;
        }
        $order = $this->get_additional_order_data([$order]);
        return $order;
    }

    /**
     * Loads additional data for the orders.
     * This includes the line items (Product details), sku of the products and tracking code
     *
     * @param array $orders - WC_Order
     * @return array Modified WC_Order object
     */
    public function get_additional_order_data($orders)
    {
        $orders = array_map(function ($order) {
            $order_data = $order->get_data();
            $items = $order->get_items();
            //Add the SKU and the data of the products
            if (is_array($items)) {
                foreach ($items as $index => $item) { //WC_Order_Item_Product 
                    $order_data['line_items'][$index] = $item->get_data();
                    $order_data['line_items'][$index]['sku'] = $item->get_product()->get_sku();
                }
            }
            //Add the tracking data
            $order_data['tracking'] = $this->get_tracking_data($order->get_id());
            return $order_data;
        }, $orders);

        return $orders;
    }

    /**
     * Fetches the tracking data form the post meta
     *
     * @param string|int $order_id - The order ID
     * @return array tracking data as an array
     */
    public function get_tracking_data($order_id)
    {
        $tracking_data = get_post_meta($order_id, 'sep_tracking_codes', true);
        return (empty($tracking_data)) ? [] : maybe_unserialize($tracking_data);
    }

    /**
     * Appends the tracking information to the order
     *
     * @param string|int $order_id - The Order ID
     * @param string $barcode - The barcode
     * @param string $provider - The service provider
     * @return string|bool Meta ID if created, true on update, false on error or same value
     */
    public function add_tracking_data($order_id, $barcode, $provider)
    {
        $tracking_data =  $this->get_tracking_data($order_id);
        $new_data = [
            'barcode' => $barcode,
            'provider' => $provider,
        ];
        array_push($tracking_data, $new_data);
        $tracking_data = maybe_serialize($tracking_data);
        return update_post_meta($order_id, 'sep_tracking_codes', $tracking_data);
    }
}

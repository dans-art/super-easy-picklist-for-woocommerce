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
        $setting_handler = new SepSettings;
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
        $sp = $setting_handler->get_shipping_providers();
        $orders = $this->get_additional_order_data($orders);
        return ['orders' => $orders, 'shipping_providers' => $sp];
    }
    /**
     * Loads a single order
     *
     * @return void
     */
    public function get_single_order($id = '')
    {
        $setting_handler = new SepSettings;

        $order = wc_get_order($id); //WC_Order
        //Filter all the elements not needed.
        if (empty($order)) {
            return false;
        }
        $sp = $setting_handler->get_shipping_providers();
        $order = $this->get_additional_order_data([$order]);
        return ['orders' => $order, 'shipping_providers' => $sp];
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
                    $order_data['line_items'][$index]['meta_data'] = $this->get_product_meta_name($order_data['line_items'][$index]);
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
     * Returns the nicename for the meta data. This works only with meta data, which are defined in "Attributes"
     *
     * @param array $product - Product array 
     * @return array The formatted meta data
     */
    public function get_product_meta_name($product)
    {
        if (!isset($product['meta_data']) or !isset($product['product_id'])) {
            return null;
        }
        $product_id = $product['product_id'];
        $product_meta_attr = get_post_meta($product_id, '_product_attributes', true); //Gets the product attributes

        //$meta is a WC_Meta_Data object
        foreach ($product['meta_data'] as $index => $meta) {
            $meta_arr = $meta->get_data(); //Get the data from WC_Meta_Data as an array
            //Attribute slug to nicename
            $meta_slug = (isset($product_meta_attr[$meta_arr['key']]['name'])) ? $product_meta_attr[$meta_arr['key']]['name'] : '';
            $product['meta_data'][$index] = $meta_arr;
            $product['meta_data'][$index]['name'] = wc_attribute_label($meta_slug);

            //Check for attributes with non string values
            if (!is_string($product['meta_data'][$index]['value'])) {
                $product['meta_data'][$index]['value'] = serialize($product['meta_data'][$index]['value']);
            }
        }
        return $product['meta_data'];
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
     * Returns the tracking data as formatted links
     *
     * @param string|int $order_id - The Order ID
     * @return string The formatted tracking code or error message
     */
    public function get_tracking_data_formatted($order_id)
    {
        $order = wc_get_order($order_id);
        $tracking_data = $this->get_tracking_data($order_id);
        $order_items = $order->get_items();
        $out = '';
        if (empty($tracking_data)) {
            return __('This order has no tracking data so far.', 'sep');
        }
        $template =  DaTemplateHandler::load_template_to_var('packed-container.html', 'backend/components');
        $format = get_option('date_format') . ' ' .get_option('time_format');

        foreach ($tracking_data as $index => $item) {
            $sp_name = (empty($item['sp_name'])) ? __('No tracking code', 'sep') : $item['sp_name'];
            $link = (empty($item['sp_code'])) ? '' : $this->get_tracking_link($item['sp_id'], $item['sp_code']);
            $date_packed = (empty($item['date_packed'])) ? '' : wp_date($format, $item['date_packed']);
            $items = "";
            //Loop the packed items
            if (is_object($item['items_packed'])) {
                foreach ($item['items_packed'] as $index => $packed_item) {
                    if (is_object($order_items[$packed_item->id])) {
                        $name = $order_items[$packed_item->id]->get_name();
                        $total = $order_items[$packed_item->id]->get_quantity();
                        $items .= '<div>' . $packed_item->quant . '/' . $total  . ' - ' . $name . '</div>';
                    }
                }
            }
            $item_template = $template;
            $item_template = str_replace('{{service_provider}}', $sp_name, $item_template);
            $item_template = str_replace('{{tracking_code}}', $item['sp_code'], $item_template);
            $item_template = str_replace('{{tracking_link}}', $link, $item_template);
            $item_template = str_replace('{{items}}', $items, $item_template);
            $item_template = str_replace('{{date}}', $date_packed, $item_template);
            $out .= $item_template;
        }

        $picked = $this->check_amount_packed($order);
        if ($picked['current'] === $picked['total']) {
            $out .= __('Super! All items picked', 'sep');
        } else {
            $out .=  sprintf(__('%d of %d items picked', 'sep'), $picked['current'], $picked['total']);
        }

        return $out;
    }

    /**
     * Returns the currently packed amount of items
     *
     * @param array $tracking_data - The tracking data from $this->get_tracking_data()
     * @param int $line_id - The Id of the line item
     * @return int The total packed amount
     */
    public function get_packed_amount($tracking_data, $line_id)
    {
        $total = 0;
        foreach ($tracking_data as $index => $item) {
            //Loop the packed items
            if (is_object($item['items_packed'])) {
                foreach ($item['items_packed'] as $index => $packed_item) {
                    if ($packed_item->id === intval($line_id)) {
                        $total += $packed_item->quant;
                    }
                }
            }
        }
        return $total;
    }

    /**
     * Checks how many items are packed
     *
     * @param object $order The WC_Order object
     * @return array The total and current item count
     */
    public function check_amount_packed($order)
    {
        $tracking_data = $this->get_tracking_data($order->get_id());
        $order_items = $order->get_items();
        $total = 0;
        $current = 0;
        foreach ($order_items as $index => $item) {
            $total += $item->get_quantity();
            $current += $this->get_packed_amount($tracking_data, $index);
        }
        return ['current' => $current, 'total' => $total];
    }

    /**
     * Converts the tracking link
     *
     * @param string|int $sp_id - Id of the service provider
     * @param string|int $barcode - The barcode
     * @return string The new link or an empty string
     */
    public function get_tracking_link($sp_id, $barcode)
    {
        $link = get_post_field('post_content', $sp_id);
        if (empty($link)) {
            return '';
        }
        return str_replace('{tracking}', $barcode, $link);
    }

    /**
     * Appends the tracking information to the order
     *
     * @param string|int $order_id - The Order ID
     * @param string $link - The link with barcode placeholder
     * @param string $sp_id - The service provider ID
     * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure or if the 
     * value passed to the function is the same as the one that is already in the database.
     * @deprecated Remove 
     * @todo: Remove, unused
     */
    public function add_tracking_data($order_id, $link, $sp_id)
    {
        $tracking_data =  $this->get_tracking_data($order_id);
        $provider_name = get_post_field('post_title', $sp_id);
        if (empty($provider_name)) {
            return __('No service provider found', 'sep');
        }
        $new_data = [
            'link' => $link,
            'name' => $provider_name,
            'id' => $sp_id,
        ];
        array_push($tracking_data, $new_data);
        $tracking_data = maybe_serialize($tracking_data);
        return update_post_meta($order_id, 'sep_tracking_codes', $tracking_data);
    }

    /**
     * Adds the information about packed items to an order
     *
     * @param int $order_id - The ID of the order
     * @param int $sp_id - The ID of the shipping provider
     * @param string $sp_code - The barcode of the sp
     * @param string $items - The items packed with this order (JSON String)
     * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure or if the 
     * value passed to the function is the same as the one that is already in the database.
     */
    public function add_packed_order($order_id, $sp_id, $sp_code, $items)
    {
        $tracking_data =  $this->get_tracking_data($order_id);
        $provider_name = get_post_field('post_title', $sp_id);
        $items = stripcslashes($items);
        $new_data = [
            'sp_id' => $sp_id,
            'sp_name' => $provider_name,
            'sp_code' => $sp_code,
            'items_packed' => json_decode($items),
            'date_packed' => time(),
        ];
        array_push($tracking_data, $new_data);
        $tracking_data = maybe_serialize($tracking_data);

        return update_post_meta($order_id, 'sep_tracking_codes', $tracking_data);
    }

    /**
     * Removes a tracking code from the given order
     *
     * @param string|int $order_id - The Order ID
     * @param string|int $sp_id - The service provider ID
     * @return string|bool error message on error, true on success
     */
    public function remove_tracking_data($order_id, $sp_id)
    {
        $tracking_data =  $this->get_tracking_data($order_id);
        foreach ($tracking_data as $index => $item) {
            if ($item['id'] == $sp_id) {
                unset($tracking_data[$index]);
            }
        }
        $tracking_data = maybe_serialize($tracking_data);
        return update_post_meta($order_id, 'sep_tracking_codes', $tracking_data);
    }
}

<?php

/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * Class description: Functions for order handling
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 *
 */

class SepOrder
{

    public static function load_order($order){
        $order_by_id = wc_get_order($order);
        if(!empty($order_by_id)){
            return $order_by_id;
        }
    }
}

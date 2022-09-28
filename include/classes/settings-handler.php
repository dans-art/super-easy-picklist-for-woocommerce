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

class SepSettings
{

    /**
     * Insert a new shipping provider
     *
     * @param string $name - The name of the sp.
     * @param string $tracking_code - The tracking code / tracking link
     * @return mixed - The post ID on success. The value 0 or WP_Error on failure.
     */
    public function save_new_shipping_provider($name, $tracking_code)
    {
        return wp_insert_post([
            'post_content' => wp_strip_all_tags($tracking_code),
            'post_title' => wp_strip_all_tags($name),
            'post_type' => 'sep_ship_prov',
            'post_status'   => 'publish'
        ], true);
    }

    /**
     * Loads the shipping providers and returns a object.
     * 
     *
     * @return object|string Object on success, error message on error
     */
    public function get_shipping_providers(){
        $sp = get_posts([
            'post_type' => 'sep_ship_prov'
        ]);
        $sp = array_map(function($item){
            $new_item = new stdClass;
            $new_item -> slug = $item -> post_name;
            $new_item -> name = $item -> post_title;
            $new_item -> link = $item -> post_content;
            return $new_item;
        }, $sp);
        s($sp);
    }
}

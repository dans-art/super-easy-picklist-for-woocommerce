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
     * Returns the shipping provider as formatted html
     *
     * @return string The html code or error message
     */
    public function get_shipping_providers_html()
    {
        $sp = $this->get_shipping_providers();
        if (empty($sp)) {
            return __('No shipping providers found.', 'sep');
        }
        //load the template
        $template =  DaTemplateHandler::load_template_to_var('tracking-item.html', 'backend/components');
        $output = '';
        foreach ($sp as $index => $item) {
            $item_template = $template;
            $item_template = str_replace('{{id}}', $item->id, $item_template);
            $item_template = str_replace('{{name}}', $item->name, $item_template);
            $item_template = str_replace('{{link}}', $item->link, $item_template);
            $item_template = str_replace('{{remove_text}}', __('Remove','sep'), $item_template);
            $output .= $item_template;
        }
        return $output;
    }

    /**
     * @todo: Make this function work
     *
     * @return void
     */
    public function remove_shipping_provider($id)
    {
        return wp_delete_post($id);
    }

    /**
     * Loads the shipping providers and returns a object.
     * 
     *
     * @return object|string Object on success, error message on error
     */
    public function get_shipping_providers()
    {
        $sp = get_posts([
            'post_type' => 'sep_ship_prov'
        ]);
        $sp = array_map(function ($item) {
            $new_item = new stdClass;
            $new_item->id = $item->ID;
            $new_item->slug = $item->post_name;
            $new_item->name = $item->post_title;
            $new_item->link = $item->post_content;
            return $new_item;
        }, $sp);

        return $sp;
    }
}

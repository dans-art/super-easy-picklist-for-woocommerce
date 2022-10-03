<?php

/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * Class description: Main Class. Includes the plugins functionalities to front- and backend. 
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class SepBackendMain extends SepHelper
{

    public function __construct()
    {
        //@todo: Check if WC is installed

        //Set paths for the template handler
        DaTemplateHandler::set_paths(SEP_PATH . 'templates', 'super-easy-picklist/templates');

        $this -> plugin_path = WP_PLUGIN_DIR . '/super-easy-picklist-for-woocommerce/';
        
        //Loads the current version to the helper class
        $this -> load_version();
        //Add the Actions
        $this -> add_actions();

    }

}

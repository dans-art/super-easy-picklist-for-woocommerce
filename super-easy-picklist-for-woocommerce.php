<?php

/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * Description: Reduce errors while packing and send customers the package tracking codes
 * Plugin URI: https://dev.dans-art.ch
 * Contributors: dansart
 * Contributors URL: http://dev.dans-art.ch
 * Tags: woocommerce, customer, tools, helper
 * Version: 0.1
 * Stable tag: 0.1
 * 
 * Requires at least: 5.4.0
 * Tested up to: 5.9
 * 
 * WC requires at least: 4.7.0
 * WC tested up to: 6.1.1
 * 
 * Requires PHP: 7.4
 * 
 * Domain Path: /languages
 * Text Domain: sep
 * 
 * Author: Dan's Art
 * Author URI: https://dev.dans-art.ch
 * Donate link: https://paypal.me/dansart13

 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * 
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//Load the Plugins 
require_once('include/plugins/da-template-handler.php');
require_once('include/plugins/da-ajax-handler.php');

// Load the classes
require_once('include/classes/ajax-functions.php');
require_once('include/classes/helper.php');
require_once('include/classes/backend-main.php');

//Define the constants
define('SEP_PATH', plugin_dir_path( __FILE__ ));
define('SEP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ));

$sep = new SepBackendMain;

<?php

/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * Class description: Various helper methods.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SepHelper
{
    protected $version = '000'; //The current plugin version. This is used to make sure that on plugin update, the styles and scripts will be cleared from the cache.
    public $plugin_path = ''; //The path to the plugin folder
    protected $ajax_included = false;


    public function add_actions()
    {
        //Add the backend menu
        register_activation_hook(SEP_PATH, [$this, 'sep_activate']);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_head', [$this, 'enqueue_ajax_functions']);

        //Enqueues scripts
        add_action('admin_enqueue_scripts', [$this, 'sep_enqueue_scripts']);

        //Add meta boxes
        add_action('add_meta_boxes', [$this, 'sep_add_order_meta_box']);
        //Ajax actions
        add_action('wp_ajax_sep_ajax_orders', [new SepAjax, 'sep_ajax_orders']);
        add_action('wp_ajax_nopriv_sep_ajax_orders', [new SepAjax, 'sep_ajax_nopriv_all']);
        add_action('wp_ajax_sep_ajax_settings', [new SepAjax, 'sep_ajax_settings']);
        add_action('wp_ajax_nopriv_sep_ajax_orders', [new SepAjax, 'sep_ajax_nopriv_all']);
    }

    /**
     * Called by the register_activation_hook
     * Registers post type 'sep_shipping_provider'
     * @return void
     */
    public function sep_activate()
    {
        add_action('init', function(){
            $sp_args = []; //Arguments for the shipping provider post type.
            register_post_type('sep_ship_prov', $sp_args);
        });

    }
    /**
     * Loads the current plugin version.
     */
    public function load_version()
    {
        if (!function_exists('get_file_data')) {
            $this->version = "000";
            return;
        }
        $plugin_meta = get_file_data($this->plugin_path . 'super-easy-picklist-for-woocommerce.php', array('Version'), 'plugin');
        $this->version = (!empty($plugin_meta[0])) ? $plugin_meta[0] : "001";
        return;
    }


    /**
     * Loads the translation of the plugin.
     * First it checks for downloaded translations by Wordpress, else it will search for the the translation in the plugin dir.
     * Located at: plugins/add-customer-for-woocommerce/languages/
     *
     * @return void
     */
    public function sep_load_textdomain()
    {
        //Search also in the wp-content/language folder
        load_textdomain('sep', $this->sep_get_home_path() . 'wp-content/languages/plugins/super-easy-picklist-for-woocommerce-' . determine_locale() . '.mo');

        //Try to load the file from the plugin-dir
        load_textdomain('sep', $this->sep_get_home_path() . 'wp-content/plugins/super-easy-picklist-for-woocommerce/languages/sep-' . determine_locale() . '.mo');
    }

    /**
     * Enqueues all the scripts and styles
     * @todo: Only enqueue if neccesary, only on settings page and order
     * @return void
     */
    public function sep_enqueue_scripts()
    {
        $this->sep_enqueue_admin_style();
        $this->sep_enqueue_admin_scripts();
    }

    /**
     * Gets the Home Path. Workaround if WP is not completely loaded yet. 
     *
     * @return string  Full filesystem path to the root of the WordPress installation. (/var/www/htdocs/)
     */
    public function sep_get_home_path()
    {
        if (function_exists('get_home_path')) {
            return get_home_path();
        }
        $home    = set_url_scheme(get_option('home'), 'http');
        $siteurl = set_url_scheme(get_option('siteurl'), 'http');

        if (!empty($home) && 0 !== strcasecmp($home, $siteurl)) {
            $wp_path_rel_to_home = str_ireplace($home, '', $siteurl); /* $siteurl - $home */
            $pos                 = strripos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']), trailingslashit($wp_path_rel_to_home));
            $home_path           = substr($_SERVER['SCRIPT_FILENAME'], 0, $pos);
            $home_path           = trailingslashit($home_path);
        } else {
            $home_path = ABSPATH;
        }

        return str_replace('\\', '/', $home_path);
    }

    public function sep_add_order_meta_box(){
        add_meta_box(
            'sep_order_meta_box',
            __('Super Easy Picklist','sep'),
            [$this, 'render_order_meta_box'],
            'shop_order', 
            'side'
        );
    }

    public function render_order_meta_box(){
        echo DaTemplateHandler::load_template_to_var('order-meta-box', 'meta-box/');
        return;
    }
    
    /**
     * Registers the custom settings Field
     *
     * @return void
     */
    public function add_menu()
    {
        $icon_url = SEP_PLUGIN_DIR_URL . 'assets/images/sep-logo.png';
        add_menu_page(
            __('Super Easy Picklist', 'sep'),
            __('Super Easy Picklist', 'sep'),
            'manage_options',
            'super-easy-picklist',
            [$this, 'render_backend_option_page'],
            $icon_url,
            58
        );
    }

    /**
     * Displays the options page
     *
     * @return void
     */
    public function render_backend_option_page()
    {
        DaTemplateHandler::load_template('backend-options-page', 'backend', ["plugin_path" => SEP_PATH . 'templates']);
        //'super-easy-picklist-for-woocommerce/templates/backend/backend-options-page.php',
    }

    /**
     * Enqueue the styles of the plugin
     * Located at: plugins/super-easy-picklist-for-woocommerce/style/admin-style.css
     *
     * @return void
     */
    public function sep_enqueue_admin_style()
    {
        wp_enqueue_style('sep-fa', 'https://use.fontawesome.com/releases/v6.2.0/css/all.css');
        wp_enqueue_style('sep-admin-style', get_option('siteurl') . '/wp-content/plugins/super-easy-picklist-for-woocommerce/style/admin-style.css', array('sep-fa'), $this->version);
    }

    /**
     * Enqueue the scripts of the plugin
     * Located at: plugins/super-easy-picklist-for-woocommerce/include/js/sep-main-script.min.js
     *
     * @return void
     */
    public function sep_enqueue_admin_scripts()
    {
        $min = ($this->is_dev_server()) ? '' : '.min';
        wp_enqueue_script('sep-admin-script', get_option('siteurl') . '/wp-content/plugins/super-easy-picklist-for-woocommerce/include/js/sep-main-script' . $min . '.js', array('wp-i18n', 'jquery'), $this->version);
        wp_set_script_translations('sep-admin-script', 'sep', SEP_PATH . "/languages");
    }

    /**
     * Called by the wp_head action. Will include some js variables for the ajax functions
     *
     * @return void
     */
    public function enqueue_ajax_functions()
    {
        if ($this->ajax_included === false) {
            //Add dynamic script to header
            echo "<script type='text/javascript' defer='defer'>
                var ajaxurl = '" . admin_url('admin-ajax.php') . "';
                var sep_plugin_dir_url = '" . SEP_PLUGIN_DIR_URL . "';
                </script>";
            $this->ajax_included = true;
        }
        return;
    }

    /**
     * Logs Events to the Simple History Plugin and to the PHP Error Log on error.
     * Some Errors get displayed to the user
     * 
     * @param string $log_type - The log type. Allowed types: added_user, failed_to_add_user
     * @param string $order_id - The order id
     * @param mixed $args - Args for the vspringf() Function. String or Int 
     * 
     * @return void
     */
    public function log_event($log_type, $order_id, ...$args)
    {
        $additional_log = array();
        $type = 'null';

        switch ($log_type) {
            default:
                $message = __('Log Type not found!', 'sep');
                break;
        }
        if (!empty($args)) {
            $msg_trans = vsprintf($message, $args);
        } else {
            $msg_trans = $message;
        }
        apply_filters('simple_history_log', "{$msg_trans} - by Super Easy Picklist", $additional_log);
        $this->sep_set_notice($msg_trans, $type, $order_id);
        return;
    }

    /**
     * Evaluates if the current server is a development server
     *
     * @return boolean
     */
    public function is_dev_server()
    {
        $url = get_site_url();
        $pos = strpos($url, "localhost/wordpress");
        if ($pos <= 8 and $pos > 2) {
            return true;
        }
        return false;
    }

    /**
     * Saves a message to be displayed as an admin_notice
     *
     * @param string $notice - The message to display
     * @param string $type - Type of message (success, error)
     * @param int $order_id - The order_id / post_id
     * @return bool True on success, false on error
     */
    public function sep_set_notice(string $notice, string $type, $order_id)
    {
        $user_id = get_current_user_id();
        $trans_id = "sep_admin_notice_{$user_id}_{$order_id}";
        $classes = "";
        switch ($type) {
            case 'error':
                $classes = 'notice notice-error';
                break;
            case 'success':
                $classes = 'notice notice-success';
                break;

            default:
                $classes = 'notice notice-info';
                break;
        }
        $notice = "<div class='{$classes}'><p>{$notice}</p></div>";
        $trans_notices = get_transient($trans_id);
        if (is_array($trans_notices)) {
            $trans_notices[] = $notice;
        } else {
            $trans_notices = array($notice);
        }
        return set_transient($trans_id, $trans_notices, 45);
    }

    /**
     * Displays the stored messages as admin_notices
     *
     * @return void
     */
    public function sep_display_notices()
    {
        add_action('admin_notices', function () {
            $user_id = get_current_user_id();
            $order_id = (!empty($_GET['post'])) ? $_GET['post'] : 0;
            $trans_id = "sep_admin_notice_{$user_id}_{$order_id}";

            $notices = get_transient($trans_id);
            if (is_array($notices)) {
                foreach ($notices as $notice) {
                    echo $notice;
                }
            }
            delete_transient($trans_id);
        });
    }
}

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


    /**
     * Adds actions, filters and shortcodes
     *
     * @return void
     */
    public function add_actions()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_head', [$this, 'sep_add_additional_js']);
        add_action('admin_head', [$this, 'sep_add_additional_js']);

        //Shortcodes
        add_shortcode( 'sep_picklist', [$this, 'render_picklist_shortcode'] );

        //Ajax actions
        add_action('wp_ajax_sep_order_functions', [new SepAjax, 'sep_order_functions']);
        add_action('wp_ajax_nopriv_sep_order_functions', [new SepAjax, 'sep_order_functions_nopriv']);
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
    /**
     * Registers the custom settings Field
     *
     * @return void
     */
    public function add_menu()
    {
        $icon_url = '';
        add_menu_page(
            __('Super Easy Picklist', 'sep'),
            __('Super Easy Picklist', 'sep'),
            'manage_options',
            'super-easy-picklist-for-woocommerce',
            [$this, 'render_picklist_backend'],
            $icon_url,
            58
        );
    }

    /**
     * Renders the picklist from the shortcode.
     *
     * @return void
     */
    public function render_picklist_shortcode(){
        return $this->load_template_to_var('frontend-options-page', 'frontend/');
    }
    /**
     * Renders the picklist for the bandend.
     *
     * @return void
     */
    public function render_picklist_backend(){
        echo $this->load_template_to_var('backend-options-page', 'backend/');
    }

    /**
     * Enqueue the styles of the plugin
     * Located at: plugins/super-easy-picklist-for-woocommerce/style/admin-style.css
     *
     * @return void
     */
    public function sep_enqueue_admin_style()
    {
        wp_enqueue_style('sep-admin-style', get_option('siteurl') . '/wp-content/plugins/super-easy-picklist-for-woocommerce/style/admin-style.css', array(), $this->version);
    }

    /**
     * Enqueue the scripts of the plugin
     * Located at: plugins/super-easy-picklist-for-woocommerce/include/js/sep-main-script.min.js
     *
     * @return void
     */
    public function sep_enqueue_admin_scripts()
    {
        $min = (WP_DEBUG) ? '' : '.min' ; //Loads the Min version of the script not WP_DEBUG
        wp_enqueue_script('sep-admin-script', get_option('siteurl') . '/wp-content/plugins/super-easy-picklist-for-woocommerce/include/js/sep-main-script'.$min.'.js', array('jquery'), $this->version);
    }


    public function sep_add_additional_js(){
            $ajax_url =  admin_url('admin-ajax.php');
            echo "<script type='text/javascript'>document.ajax_url = '$ajax_url';</script>";
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
     * Loads template to variable.
     * @param string $template_name - Name of the template without extension
     * @param string $subfolder - Name of the Subfolder(s). Base folder is Plugin_dir/templates/
     * @param string $template_args - Arguments to pass to the template
     * 
     * @return string Template content or error Message
     */
    public function load_template_to_var(string $template_name = '', string $subfolder = '', ...$template_args)
    {
        $args = get_defined_vars();
        $path = $this->get_template_location($template_name, $subfolder);

        if (file_exists($path)) {
            ob_start();
            include($path);
            $output_string = ob_get_contents();
            ob_end_clean();
            wp_reset_postdata();
            return $output_string;
        }
        return sprintf(__('Template "%s" not found! (%s)', 'sep'), $template_name, $path);
    }

    /**
     * Function to find the template file. First the Child-Theme will be checked. If not found, the file in the plugin will be returned.
     *
     * @param string $template_name - The name of the template.
     * @param string $subfolder - The subfolder the template is in. With tailing \
     * @return string The location of the file.
     */
    public function get_template_location($template_name, $subfolder)
    {
        //Checks if the file exists in the theme or child-theme folder
        $locate = locate_template('woocommerce/super-easy-picklist/' . $subfolder . $template_name . '.php');
        if (empty($locate)) {
            return str_replace('\\', '/', $this->plugin_path . 'templates/' . $subfolder . $template_name . '.php');
        }
        return str_replace('\\', '/', $locate);
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

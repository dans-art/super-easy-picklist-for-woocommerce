<?php

/**
 * This Template renders the backend page of the settings.
 * Wordpress Backend -> Super Easy Picklist
 * 
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$sep = new SepBackendMain;
$sep->sep_enqueue_admin_style();
$sep->sep_enqueue_admin_scripts();

echo $sep->load_template_to_var('backend-options-page-main', 'backend/');
?>
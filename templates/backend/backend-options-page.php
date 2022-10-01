<?php

/**
 * This Template renders the backend page of the settings.
 * Wordpress Backend -> Super Easy Picklist
 * You can replace this file by putting your own version into the theme folder: [theme]/super-easy-picklist/templates/backend
 * 
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$sep_helper = new SepHelper;
add_filter('admin_footer_text', [$sep_helper, 'admin_footer_filter'], 1, 10);

?>

<h2><?php echo __('Super Easy Picklist', 'sep'); ?></h2>

<?php
$current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : null;
?>

<nav class="nav-tab-wrapper">
    <a href="?page=super-easy-picklist" class="nav-tab <?php if ($current_tab === null) {echo 'nav-tab-active';} ?>"><?php echo __('Picklist', 'sep'); ?></a>
    <a href="?page=super-easy-picklist&tab=settings" class="nav-tab <?php if ($current_tab === 'settings') {echo 'nav-tab-active';} ?>"><?php echo __('Settings', 'sep'); ?></a>
    <a href="?page=super-easy-picklist&tab=about" class="nav-tab <?php if ($current_tab === 'about') { echo 'nav-tab-active';} ?>"><?php echo __('About and help', 'sep'); ?></a>
</nav>

<div class="sep-options-tab tab-content">
    <?php
    switch ($current_tab) {
        case 'settings':
            echo DaTemplateHandler::load_template_to_var('backend-options-page-settings', 'backend/');
            break;
        case 'about':
            echo DaTemplateHandler::load_template_to_var('backend-options-page-about', 'backend/');
            break;
        default:
            echo DaTemplateHandler::load_template_to_var('backend-options-page-main', 'backend/');
            break;
    }
    ?>
</div>
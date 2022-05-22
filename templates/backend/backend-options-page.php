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
?>

<h2><?php echo __('Super Easy Picklist', 'sep'); ?></h2>

<?php
$current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : null;
?>

<nav class="nav-tab-wrapper">
    <a href="?page=super-easy-picklist-for-woocommerce%2Ftemplates%2Fbackend%2Fbackend-options-page.php" class="nav-tab <?php if ($current_tab === null) {echo 'nav-tab-active';} ?>"><?php echo __('Picklist', 'sep'); ?></a>
    <a href="?page=super-easy-picklist-for-woocommerce%2Ftemplates%2Fbackend%2Fbackend-options-page.php&tab=service_provider" class="nav-tab <?php if ($current_tab === 'service_provider') {echo 'nav-tab-active';} ?>"><?php echo __('Service Provider', 'sep'); ?></a>
</nav>

<div class="sep-options-tab tab-content">
    <?php
    switch ($current_tab) {
        case 'service_provider':
            echo $sep->load_template_to_var('backend-options-page-service-provider', 'backend/');
            break;
        default:
            echo $sep->load_template_to_var('backend-options-page-main', 'backend/');
            break;
    }
    ?>
</div>
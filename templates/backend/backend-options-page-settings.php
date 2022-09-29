<?php

/**
 * This Template renders the backend page of the shipping providers and other settings.
 * Wordpress Backend -> Super Easy Picklist -> [Tab] Settings
 * 
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$page = esc_url(add_query_arg('tab', admin_url('options.php')));
$settings_handler = new SepSettings;
?>

<div id="sep-settings">
    <form id='sep_settings_page' action="<?php echo $page; ?>" method="post" enctype="multipart/form-data">
        <h2><?php echo __('Shipping providers', 'sep'); ?></h2>
        <h3><?php echo __('Available Shipping Services', 'sep'); ?></h3>
        <div id="sep-shipping-providers">
            <?php echo $settings_handler->get_shipping_providers_html(); ?>
        </div>
        <h3><?php echo __('Add new shipping provider', 'sep'); ?></h3>
        <div id='sep-add-shipping-provider-container'>
            <fieldset>
                <label for="sep-add-shipping-provider-name" class=""><?php echo __('Name', 'sep'); ?></label>
                <input type="text" name="sep-add-shipping-provider-name" id="sep-add-shipping-provider-name" />
            </fieldset>
            <fieldset>
                <label for="sep-add-shipping-provider-link" class="">
                    <span><?php echo __('Tracking link with Placeholder', 'sep'); ?></span>
                    <br/>
                    <span><?php echo __('({tracking} for the tracking number)', 'sep'); ?></span>
                </label>
                <input type="text" name="sep-add-shipping-provider-link" id="sep-add-shipping-provider-link" />
            </fieldset>
            <button id="sep-save-shipping-provider" class="button"><?php echo __('Save shipping provider', 'sep'); ?></button>
            <div id="sep-add-sp-errors" class="sep-error"></div>
        </div>
        <h2><?php echo __('Customer information', 'sep'); ?></h2>
        <div id="sep-general-settings">
            <fieldset>
                <input type="checkbox" id="sep-customer-info-check" name="sep-customer-info-check" value="true" />
                <label for="sep-customer-info-check" class=""><?php echo __('Send e-mail with tracking code on status change to:', 'sep'); ?></label>
                <select name="sep-customer-info-status" id="sep-customer-info-status">
                    <option value="null">Null</option>
                </select>
            </fieldset>
            <div id="sep-customer-info-messages"></div>
        </div>
    </form>
</div>
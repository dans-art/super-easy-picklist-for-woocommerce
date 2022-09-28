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
    <div id="sep-shipping-providers">
        <?php  $settings_handler->get_shipping_providers();?>
    </div>
    <form id='sep_settings_page' action="<?php echo $page; ?>" method="post" enctype="multipart/form-data">
        <div id='sep-add-shipping-provider-container'>
            <fieldset>
                <label for="sep-add-shipping-provider-name" class=""><?php echo __('Name', 'sep'); ?></label>
                <input type="text" name="sep-add-shipping-provider-name" id="sep-add-shipping-provider-name" />
            </fieldset>
            <fieldset>
                <label for="sep-add-shipping-provider-link" class=""><?php echo __('Tracking link with Placeholder ({tracking} for the tracking number)', 'sep'); ?></label>
                <input type="text" name="sep-add-shipping-provider-link" id="sep-add-shipping-provider-link" />
            </fieldset>
            <button id="sep-save-shipping-provider"><?php echo __('Save shipping provider', 'sep'); ?></button>
            <div id="sep-add-sp-errors" class="sep-error"></div>
        </div>
    </form>
</div>
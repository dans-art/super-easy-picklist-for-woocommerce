<?php

/**
 * This Template renders the backend page for Picklist.
 * Wordpress Backend -> Super Easy Picklist
 * 
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$page = esc_url(add_query_arg('tab', admin_url('options.php')));
$order = isset($_GET['order_id']) ? $_GET['order_id'] : false;
?>
<?php
//Display the order form to search for an order
if (!$order) : ?>
    <form id='sep_picklist_page' action="<?php echo $page; ?>" method="post" enctype="multipart/form-data">
        <div id='sep-order-search-input-container'>
            <div class=""><?php echo __('Search by order number or customer name','sep'); ?></div>
            <input type="text" name="sep-order-search-input" id="sep-order-search-input" />
            <button id="sep-order-search"><?php echo __('Search Order', 'sep'); ?></button>
        </div>
    </form>
    <div id="order-section-content"></div>
    <div id="order-section-errors" class="sep-errors"></div>
<?php
//Displays the picklist
else : ?>
<?php endif; ?>
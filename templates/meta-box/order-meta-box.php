<?php

/**
 * This Template renders the meta box at the order page for Picklist.
 * 
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
global $post;
$order_handler = new SepOrder;
?>

<button id="open-picklist-button" class="button"><?php echo __('Picklist', 'sep') ?></button>
<h3><?php echo __('Tracking Data','sep'); ?></h3>
<div>
    <?php echo $order_handler->get_tracking_data_formatted($post->ID); ?>
</div>


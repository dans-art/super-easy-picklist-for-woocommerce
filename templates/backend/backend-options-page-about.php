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
?>
<div id="sep-about-message">
<?php
echo __('Thanks for using the Plugin. If you have any issues, you can open a ticket on the official Wordpress Plugin page or on Github.', 'sep') . '<br/>';
echo __('If you like to give me quality feedback about the plugin, please fill out the survey.', 'sep') . '<br/>';
echo __('Thanks a lot!', 'sep') . '<br/>';
?>
</div>
<div id="sep-links">
    <ul>
        <li><a href="https://profiles.wordpress.org/dansart/#content-plugins" target="_blank">Wordpress plugin page</li>
        <li><a href="https://github.com/dans-art" target="_blank">Github</li>
        <li><a href="https://dev.dans-art.ch/" target="_blank">Website Dan's Art</li>
        <li><a href="" target="_blank"><?php echo __('Survey about Super Easy Picklist','sep'); ?></li>
    </ul>
</div>
<?php
/**
 * Plugin Name: Lead Forms Builder
 * Description: Build responsive custom HTML/CSS/JS forms, embed with shortcode, and collect leads.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: lead-forms-builder
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LFB_PATH', plugin_dir_path(__FILE__));
define('LFB_URL', plugin_dir_url(__FILE__));

require_once LFB_PATH . 'includes/class-lfb-plugin.php';

add_action('plugins_loaded', static function () {
    \LFB\Plugin::init();
});

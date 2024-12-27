<?php

/**
 * Plugin Name: GoDaddy AI Assistant
 * Description: Get help with your shop: create products, write articles, find documentation, and more. Powered by AI.
 * Version: 0.3.1
 * Author: GoDaddy
 */

use GoDaddy\MWC\WordPress\Assistant\Assistant;

if (class_exists('GoDaddy\MWC\WordPress\Assistant\Admin')) {
    return;
}

if (defined('GD_ASSISTANT_LOCAL')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

require_once dirname(__FILE__) . '/inc/class-assistant.php';

new Assistant();

<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           Wpsec
 *
 * @wordpress-plugin
 * Plugin Name:       WPSEC - WP Captcha Plugin
 * Plugin URI:        godaddy.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.2
 * Author:            GoDaddy
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpsec-wp-cp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define("WPSEC_WP_CP_VERSION", "1.0.2");

require_once __DIR__ . '/vendor/autoload.php';

use Wpsec\captcha\core\CaptchaCore;

$core = new CaptchaCore( 'wpsec-wp-cp' );

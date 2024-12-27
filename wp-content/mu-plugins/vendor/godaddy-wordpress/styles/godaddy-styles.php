<?php
/**
 * Plugin Name: GoDaddy WordPress Styles
 * Plugin URI: https://godaddy.com/
 * Description: GoDaddy WordPress Styles Description
 * Version: 2.0.2
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: GoDaddy
 * Author URI: https://godaddy.com
 * Text Domain: godaddy-styles
 * Domain Path: /languages
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 *
 * @package GoDaddy_Styles
 */

namespace GoDaddy\Styles;

defined( 'ABSPATH' ) || exit;

// Guard the plugin from initializing more than once.
if ( ! class_exists( StylesLoader::class ) ) {
	require_once dirname( __FILE__ ) . '/StylesLoader.php';
}

StylesLoader::getInstance()->setBasePath( plugin_dir_path( __FILE__ ) );
StylesLoader::getInstance()->setBaseUrl( plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', array( StylesLoader::getInstance(), 'boot' ) );

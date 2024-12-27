<?php
/**
 * Plugin Name: System Plugin
 * Version: 4.154.0
 * License: GPL-2.0
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gd-system-plugin
 * Domain Path: /gd-system-plugin/languages
 *
 * This plugin, like WordPress, is licensed under the GPL.
 * Use it to make something cool, have fun, and share what you've learned with others.
 *
 * Copyright © 2020 GoDaddy Operating Company, LLC. All Rights Reserved.
 */

namespace WPaaS;


if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( class_exists( '\WPaaS\Plugin' ) ) {

	return;

}


require_once __DIR__ . '/gd-system-plugin/includes/autoload.php';
require_once __DIR__ . '/gd-system-plugin/includes/deprecated.php';


final class Plugin {

	use Singleton, Helpers;

	/**
	 * Arary of plugin data.
	 *
	 * @var array
	 */
	public static $data = [];

	/**
	 * Plugin configs object.
	 *
	 * @var Configs
	 */
	public static $configs;

	/**
	 * Class constructor.
	 */
	private function __construct() {

		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {

			return;

		}

		self::$data['version']    = '4.154.0';
		self::$data['basename']   = plugin_basename( __FILE__ );
		self::$data['base_dir']   = __DIR__ . '/gd-system-plugin/';
		self::$data['assets_url'] = home_url( 'wp-content/mu-plugins/gd-system-plugin/assets/', is_ssl() ? 'https' : null );
		self::$data['assets_dir'] = WPMU_PLUGIN_DIR . '/gd-system-plugin/assets/';

		load_muplugin_textdomain( 'gd-system-plugin', 'gd-system-plugin/languages' );

		self::$configs = new Configs();

        new Activity_logger();

		$api = new API();
		new Feature_Flag( $api );

		new Cache(); // deprecated
		$cache = new Cache_V2($api);

		if ( ( self::is_wp_cli() || self::is_wp_cron() ) && ! self::is_wpaas_v2() ) {
			new Rewrite_Static_Assets();
		}


		/**
		 * Filter the plugin configs object.
		 *
		 * @since 2.0.0
		 *
		 * @var stdClass
		 */
		self::$configs = apply_filters( strtolower( str_replace( '\\', '_', get_class( self::$configs ) ) ), self::$configs ); // @codingStandardsIgnoreLine

		if ( ! $this->validate_wpaas() ) {

			return;

		}

		$this->setup_env_vars();
		$smart_updates = new Smart_Update( $api );

		new WP_Mirror();
		new Hotfixes( $api ); // Load these early.
		new Auto_Updates( $api );
		new Disallowed_Plugins( $api );
		new GMV();
		new Bundled_Plugins();
		new CDN();
		new Gravity_Forms();
		new Sucuri_Scanner();
		new Worker();
		new \WPaaS_Deprecated();
		new Yoast_SEO();

		/**
		 * We can stop here in CLI mode.
		 */
		if ( self::is_wp_cli() ) {

			new CLI();

			return;

		}

		new Change_Domain();
		new Debug_Mode();
		new RAD( $api );
		new REST_API( $api, $cache, $smart_updates );
		new RUM();
		new SSO( $api );
		new Temp_Domain( $api );
		new Admin\Bar();
		new Admin\Block_Count();
		new Admin\Dashboard_Widgets();
		new Admin\Feedback();
		new Admin\Product_Events();
		new Admin\Recommended_Plugins_Tab();
		new Admin\Growl();
		new Admin\Site_Health();
		new Admin\Themes_Tab( $api );
		new XMLRPC();

                /**
                 * Update php version admin notice.
                 *
                 * @since php version is less than 8.0
                 */
                if (version_compare(PHP_VERSION, 8.0) < 0) {
                    $update_php_version_alert_text = self::get_update_php_alert_text();
                    new Admin\Notice( $update_php_version_alert_text, [ 'error' ] );
                }

		/**
		 * Staging site admin notice.
		 *
		 * @since 2.0.11
		 */
		if ( self::is_staging_site() ) {

			new Admin\Notice( __( 'Note: This is a staging site.', 'gd-system-plugin' ), [ 'error' ] );

		}

        $cdn_full_page = defined('GD_CDN_FULLPAGE') ? GD_CDN_FULLPAGE : false;

        if ( is_admin() && false === $cdn_full_page && $GLOBALS['wpaas_feature_flag']->get_feature_flag_value('cdn_cohort_2', false) ) {
            new Admin\Notice(
                __('Within the next 14 days, we are updating our Content Delivery Network (CDN). You can expect to see improved performance, with faster load times and a more secure environment for your data. We are committed to providing you with the best possible experience when using WordPress.', 'gd-system-plugin'),
                ['info'], 'activate_plugins', true
            );
        }

		$cdn_static = defined('GD_CDN_ENABLED') ? GD_CDN_ENABLED : false;
		if ( is_admin() && false === $cdn_static && $GLOBALS['wpaas_feature_flag']->get_feature_flag_value('cdn_static_enable', false) ) {
			new Admin\Notice(
				__('Within the next 30 days, we are enabling our improved Content Delivery Network (CDN) on your site. You can expect to see improved performance, specifically with the image assets on your site. We are committed to providing you with the best possible experience when using WordPress.', 'gd-system-plugin'),
				['info'], 'activate_plugins', true
			);
		}

	}

	/**
	 * Setup commonly used env var for bundled plugins to consume
	 *
	 * @return void
	 */
	private function setup_env_vars() {

		if ( ! getenv( 'SERVER_ENV' ) ) {

			putenv( 'SERVER_ENV=' . self::get_env() );

		}

		if ( ! getenv( 'SITE_UID' ) && defined( 'GD_ACCOUNT_UID' ) && GD_ACCOUNT_UID ) {

			putenv( 'SITE_UID=' . GD_ACCOUNT_UID );

		}

	}

	/**
	 * Verify that we are running on WPaaS.
	 *
	 * @return bool
	 */
	private function validate_wpaas() {

		if ( self::is_wpaas() ) {

			return true;

		}

		/**
		 * Filter self-destruct mode.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		$self_destruct = (bool) apply_filters( 'wpaas_self_destruct_enabled', ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) );

		/**
		 * If a WPaaS site has been migrated away to a different host
		 * we will attempt to silently delete this System Plugin from
		 * the filesystem.
		 *
		 * Self-destruct mode is disabled when running in debug mode.
		 */
		if ( $self_destruct ) {

			if ( ! class_exists( 'WP_Filesystem' ) ) {

				require_once ABSPATH . 'wp-admin/includes/file.php';

			}

			WP_Filesystem();

			global $wp_filesystem;

			$wp_filesystem->delete( self::$data['base_dir'], true );
			$wp_filesystem->delete( __FILE__ );

		}

		return false;

	}
}

plugin();

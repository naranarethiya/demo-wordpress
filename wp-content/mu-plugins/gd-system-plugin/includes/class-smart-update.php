<?php

namespace WPaaS;

final class Smart_Update {
	use Helpers;

	const LOG_DIR = 'wpaas-updates-log';
	/**
	 * Instance of the API.
	 *
	 * @var API_Interface
	 */
	private $api;

	/**
	 * @var string
	 */
	private $log_dir;

	/**
	 * Class constructor.
	 */
	public function __construct( API_Interface $api ) {

		$this->api     = $api;
		$this->log_dir = WP_CONTENT_DIR.'/'.self::LOG_DIR;

		if (defined('WPAAS_SMART_PLUGIN_UPDATE_DISABLED') && WPAAS_SMART_PLUGIN_UPDATE_DISABLED) {
			return;
		}

		$is_cron = isset($_GET['hook']) && isset($_GET['ts']) && isset($_GET['hash']);
		if ( $is_cron &&
			defined( 'GD_PLAN_NAME' ) &&
			self::get_plan_type( GD_PLAN_NAME ) === 'ultimate' &&
			$GLOBALS['wpaas_feature_flag']->get_feature_flag_value('smart_plugins_enabled', false )
			) {
			add_filter( 'pre_site_transient_update_plugins', function( $pre_site_transient, $transient ){
				return [];
			}, 10, 2 );
		}

		if ( defined( 'GD_PLAN_NAME' ) &&
			 self::get_plan_type( GD_PLAN_NAME ) === 'ultimate' &&
		     $GLOBALS['wpaas_feature_flag']->get_feature_flag_value( 'smart_plugins_enabled', false )
		) {
			add_action( 'admin_enqueue_scripts', array( $this, 'smart_updates_notice' ), - PHP_INT_MAX );
		}

		$this->set_custom_cron_interval();

		add_action( 'wpaas_smart_update_cleanup_hook', [ $this, 'smart_update_cleanup' ]);

		if ( ! wp_next_scheduled( 'wpaas_smart_update_cleanup_hook' ) ) {
			wp_schedule_event( time() , 'two_days_seconds', 'wpaas_smart_update_cleanup_hook');
		}

		if ( isset( $_COOKIE['wpaas_spu_wordpress_test'] ) ) {
			if ( get_transient( 'wpaas_smart_update_token' ) === $_COOKIE['wpaas_spu_wordpress_test'] ||  $this->is_test_http_request_valid() ) {
				$this->start_spu_test();
			}
		}
	}

	public function smart_updates_notice() {
		wp_enqueue_script( 'wpaas-smart-updates-status', Plugin::assets_url( 'js/smart-updates-status.js' ), array( 'jquery' ) );
		wp_localize_script(
			'wpaas-smart-updates-status',
			'wpaas_smart_updates_status_object',
			array(
				'ajaxUrlFetch'    => esc_url_raw( rest_url() ) . 'wpaas/v1/smart-updates',
				'ajaxUrlDismiss'  => esc_url_raw( rest_url() ) . 'wpaas/v1/smart-updates/dismiss',
				'nonce'           => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	private function set_custom_cron_interval() {

		add_filter( 'cron_schedules', function ( $schedules ) {
			$schedules['two_days_seconds'] = array(
				'interval' => 2 * 24 * 60 * 60,
				'display' => esc_html__('Every 48 hours'),);
			return $schedules;
		} );

	}
	/**
	 * This function start logging php errors in file
	 * @return void
	 */
	private function start_spu_test() {
		if ( ! file_exists ( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
		}

		register_shutdown_function( [ $this, 'handleError' ] );
		if ( get_transient( 'wpaas_smart_update_token' ) !== $_COOKIE['wpaas_spu_wordpress_test'] ) {
			set_transient( 'wpaas_smart_update_token', $_COOKIE['wpaas_spu_wordpress_test'], 24 * 60 * 60 );
		}
	}

	public function handleError() {
		$lastError     = error_get_last();

	 	if( $lastError ) {
			$log_file      = $this->log_dir . '/' . get_transient( 'wpaas_smart_update_token' ) . '.log';
			$error_message = 'Time: ' . date('m/d/Y h:i:s a', time()) .  ' Type: ' . $lastError['type'] . ' Message: ' . $lastError['message'] . ' File: ' . $lastError['file'] . ' Line: ' . $lastError['line'] . PHP_EOL;
			error_log( $error_message, 3 , $log_file);
		}

	}

	public function smart_update_cleanup() {
		if ( ! class_exists( 'WP_Filesystem' ) ) {

			require_once ABSPATH . 'wp-admin/includes/file.php';

		}
		$log_files = list_files( $this->log_dir );
		if ( ! $log_files ) {
			return;
		}

		for ( $i = 0; $i < count( $log_files ); $i++ ) {

			$file_creation_date = filectime( $log_files[$i] );

			if ( time() - $file_creation_date > 24 * 60 * 60 ) { // 24hours
				wp_delete_file( $log_files[$i] );
			}
		}
	}

	public function is_test_http_request_valid() {
		$headers                   = [];
		$headers['x-wp-nonce']     = $_SERVER['HTTP_X_WP_NONCE'];
		$headers['x-wp-origin']    = $_SERVER['HTTP_X_WP_ORIGIN'];
		$headers['x-wp-signature'] = $_SERVER['HTTP_X_WP_SIGNATURE'];
		$headers['x-wp-bodyhash']  = $_SERVER['HTTP_X_WP_BODYHASH'];

		if ( $headers['x-wp-origin'] != GD_TEMP_DOMAIN ) {
			return false;
		}

		$api_url = sprintf('%s/validate', $this->api->wp_public_api_url());

		$response = wp_remote_request(
			esc_url_raw(  $api_url ),
			[
				'method'   => 'POST',
				'blocking' => true,
				'headers'  => array_merge( [
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				], $headers ),
			]
		);

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );
		return $body['validated'] ?? false;
	}

	public static function isPluginUpdated( $plugins, $plugin_upgrade ) {

		foreach ( $plugins as $plugin ) {

			if ( $plugin['Name'] === $plugin_upgrade['upgradeData'][0]['name'] ) {

				if ( version_compare( $plugin['Version'], $plugin_upgrade['upgradeData'][0]['nextVersion'] ) === 0 ||
					version_compare( $plugin['Version'], $plugin_upgrade['upgradeData'][0]['nextVersion'] ) === 1 ) {

					return true;

				}
			}
		}

		return false;
	}

	/**
	 * This function returns array of plugins that need an update
	 * @return array
	 */
	public function get_plugin_updates() {
		$this->include_upgrade_functions();
		$plugins_info = get_plugin_updates();
		$plugins_to_update = [];

		foreach ($plugins_info as $key => $value) {
			// Only return plugin if it has auto-update option enabled
			if (in_array($key, get_site_option( 'auto_update_plugins', [] ))) {
				$plugins_to_update[] = [
					'name' => $value->Name,
					'slug' => $key,
					'currentVersion' => $value->Version,
					'nextVersion' => $value->update->new_version
				];
			}
		}

		$plugins_to_update = apply_filters( 'wpaas_smart_plugin_update_filter', $plugins_to_update );

		return $plugins_to_update;
	}

	/**
	 * This function updates and activates plugins
	 * @param $plugins array | string List of plugins or a single plugin
	 * @return array
	 */
	public function update_plugins($plugins) {
		$this->include_upgrade_functions();
		$upgrader = new \Plugin_Upgrader(new Plugin_Update_Skin());

		$active_plugins = array_filter($plugins, function($x) { return !(is_plugin_active($x) === false); });
		$results = $upgrader->bulk_upgrade($plugins);

		foreach ( $results as $key => $result ) {
			// Plugin update returns array if update is successful, or true if plugin is already updated
			if ( is_array($result) || $result === true ){
				$results[$key] = true;
			} else {
				$results[$key] = false;
			}
		}

		activate_plugins($active_plugins);

		return $results;
	}

	/**
	 * This function imports required files for plugin upgrade
	 */
	private function include_upgrade_functions() {
		if ( !function_exists('get_plugin_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		if ( !class_exists('Plugin_Upgrader')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( !function_exists('request_filesystem_credentials()') ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

	}
}
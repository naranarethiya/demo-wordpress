<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Auto_Updates {
	/**
	 * Instance of the API.
	 *
	 * @var API_Interface
	 */
	private $api;

	/**
	 * Class constructor.
	 */
	public function __construct( API_Interface $api ) {

		$this->api = $api;

		// This prevents the auto-update mechanism from upgrading WordPress core
		// files, which is very important on a managed platform. Do not remove!
		add_filter( 'auto_update_core', '__return_false', PHP_INT_MAX );

		add_filter( 'automatic_updates_send_email', '__return_false', PHP_INT_MAX );
		add_filter( 'enable_auto_upgrade_email', '__return_false', PHP_INT_MAX );
		add_filter( 'automatic_updates_send_debug_email', '__return_false', PHP_INT_MAX );
		add_filter( 'auto_core_update_send_email', '__return_false', PHP_INT_MAX );
		add_filter( 'send_core_update_notification_email', '__return_false', PHP_INT_MAX );

		add_filter( 'user_has_cap', [ $this, 'spoof_update_core_cap' ], PHP_INT_MAX );

		add_filter( 'auto_plugin_update_send_email', [ $this, 'check_theme_plugin_updates_failed' ], 10, 2 );
		add_filter( 'auto_theme_update_send_email', [ $this, 'check_theme_plugin_updates_failed' ], 10, 2 );

		$this->unhook_core_update_nags();

		$this->set_auto_update_for_customer();
		$this->migrate_twentytwentythree();

		add_action( 'init', [ $this, 'init' ], - PHP_INT_MAX );

		add_action(
			'upgrader_process_complete',
			[
				$this,
				'enable_auto_update_for_new_plugin_and_theme',
			],
			10,
			2
		);
		add_action( 'delete_plugin', [ $this, 'remove_plugin_from_previous_state' ] );
		add_action( 'delete_theme', [ $this, 'remove_theme_from_previous_state' ] );

		if ( $GLOBALS['wpaas_feature_flag']->get_feature_flag_value('plugin_update_event', false) ) {
			add_action('automatic_updates_complete', [ $this, 'log_auto_update_event_metadata' ], 10, 1);
		}
	}

	/**
	 * Initialize script.
	 *
	 * @action init
	 */
	public function init() {

		$action              = filter_input( INPUT_GET, 'wpaas_action' );
		$nonce               = filter_input( INPUT_GET, 'wpaas_nonce' );
		$auto_updates_status = filter_input( INPUT_GET, 'wpaas_auto_updates_status' );

		if (
			'toggle_auto_update' !== $action
			||
			false === wp_verify_nonce( $nonce, 'wpaas_toggle_auto_update' )
			||
			! isset( $auto_updates_status )
		) {

			return;

		}

		self::toggle_auto_updates($auto_updates_status);

		Admin\Growl::add( $auto_updates_status === 'disabled' ? __( 'Auto-updates enabled!', 'gd-system-plugin' ) : __( 'Auto-updates disabled!', 'gd-system-plugin' ) );

		wp_safe_redirect(
			esc_url_raw(
				remove_query_arg(
					[
						'GD_COMMAND', // Backwards compat
						'wpaas_action',
						'wpaas_nonce',
						'wpaas_auto_updates_status',
					]
				)
			)
		);

		exit;

	}

	/**
	 * Toggles auto update for themes and plugins.
	 *
	 * @param $auto_updates_status - Current state of Auto-updates..
	 *
	 * @return string | false - State of Auto-updates after toggle.
	 */
	public static function toggle_auto_updates( $auto_updates_status ) {

        /** Logg user action */
		$toggle_action = $auto_updates_status === 'disabled' ? 'enabled' : 'disabled';
		$GLOBALS['wpaas_activity_logger']->log_sp_action(get_current_user_id(),
			sprintf('Auto-updates settings changed: %s', $toggle_action)
		);

		if ( $auto_updates_status === 'disabled' ) {

			update_option( 'mwp_previous_plugins_state', array_keys( apply_filters( 'all_plugins', get_plugins() ) ) );
			update_option( 'mwp_previous_themes_state', array_keys( apply_filters( 'all_themes', wp_get_themes() ) ) );

		}

		update_option( 'mwp_auto_updates_status', $auto_updates_status === 'disabled' ? 'enabled' : 'disabled' );
		update_option( 'auto_update_plugins', $auto_updates_status === 'disabled' ? array_keys( apply_filters( 'all_plugins', get_plugins() ) ) : [] );
		update_option( 'auto_update_themes', $auto_updates_status === 'disabled' ? array_keys( apply_filters( 'all_themes', wp_get_themes() ) ) : [] );

		return get_option('mwp_auto_updates_status');
	}

	/**
	 * Prevent users from having the `update_core` capability.
	 *
	 * @filter user_has_cap
	 *
	 * @param array $allcaps
	 *
	 * @return array
	 */
	public function spoof_update_core_cap( array $allcaps ) {

		$allcaps['update_core'] = false;

		return $allcaps;

	}

	/**
	 * Updates site options 'mwp_previous_plugins_state'. $plugin_file is name of a plugin to be removed from the state.
	 *
	 * @action delete_plugin
	 *
	 * @param $plugin_file
	 *
	 * @return void
	 */
	public function remove_plugin_from_previous_state( $plugin_file ) {

		if ( get_option( 'mwp_previous_plugins_state' ) ) {

			update_option( 'mwp_previous_plugins_state', array_diff( array_keys( apply_filters( 'all_plugins', get_plugins() ) ), array( $plugin_file ) ) );

		}
	}

	/**
	 * Updates site options 'mwp_previous_themes_state'. $stylesheet is name of the theme to be removed from state.
	 *
	 * @action delete_theme
	 *
	 * @param $stylesheet
	 *
	 * @return void
	 */
	public function remove_theme_from_previous_state( $stylesheet ) {

		if ( get_option( 'mwp_previous_themes_state' ) ) {

			update_option( 'mwp_previous_themes_state', array_diff( array_keys( apply_filters( 'all_themes', wp_get_themes() ) ), array( $stylesheet ) ) );

		}

	}

	/**
	 * Migrate old plugin and themes to auto update enable option.
	 *
	 * @return void
	 */
	public function migrate_old_plugins_and_themes() {

		if ( ! get_option( 'mwp_previous_plugins_state' ) ) {
			add_option( 'mwp_previous_plugins_state', array_keys( apply_filters( 'all_plugins', get_plugins() ) ) );
		} else {
			update_option( 'mwp_previous_plugins_state', array_keys( apply_filters( 'all_plugins', get_plugins() ) ) );
		}
		update_option( 'auto_update_plugins', array_keys( apply_filters( 'all_plugins', get_plugins() ) ) );

		if ( ! get_option( 'mwp_previous_themes_state' ) ) {
			add_option( 'mwp_previous_themes_state', array_keys( apply_filters( 'all_themes', wp_get_themes() ) ) );
		} else {
			update_option( 'mwp_previous_themes_state', array_keys( apply_filters( 'all_themes', wp_get_themes() ) ) );
		}
		update_option( 'auto_update_themes', array_keys( apply_filters( 'all_themes', wp_get_themes() ) ) );

	}

	/**
	 * Toggle users plugins and themes auto update.
	 *
	 * @action upgrader_process_complete
	 *
	 * @return void
	 */
	public function enable_auto_update_for_new_plugin_and_theme( $upgrader, $hook_extra ) {

		if ( get_option( 'mwp_auto_updates_status', 'disabled' ) === 'disabled' ) {

			return;

		}

		if ( $hook_extra['action'] == 'install' ) {

			switch ( $hook_extra['type'] ) {

				case 'plugin':
					$this->check_state(
						array_keys( apply_filters( 'all_plugins', get_plugins() ) ),
						(array) get_option( 'mwp_previous_plugins_state', array() ),
						'plugins'
					);
					break;

				case 'theme':
					$this->check_state(
						array_keys( apply_filters( 'all_themes', wp_get_themes() ) ),
						(array) get_option( 'mwp_previous_themes_state', array() ),
						'themes'
					);
					break;
			}
		}
	}

	/**
	 * Send plugin update event metadata data to ESSP.
	 *
	 * @action automatic_updates_complete
	 *
	 * @return void
	 */
	public function log_auto_update_event_metadata( $results ) {
		$parsed_url = parse_url(home_url());
		foreach ( $results['plugin'] as $plugin ) {
			$params = array(
				"pluginSlug"         => $plugin->item->slug,
				"pluginFromVersion"  => $plugin->item->current_version,
				"pluginToVersion"    => $plugin->item->new_version,
				"pluginUpdateStatus" => $plugin->result === true,
				"pluginUpdateType"   => "automatic",
				"domain"             => $parsed_url['host']
			);
			$this->api->send_automatic_plugin_update_metadata( $params );
		}
	}

	/**
	 * Compares previous and current state.
	 *
	 * 1. Creates $current_state and $previous_state diff to recognize new added plugins/themes
	 *
	 * @param $current_state
	 * @param $previous_state
	 * @param $entity
	 *
	 * @return void
	 */
	private function check_state( $current_state, $previous_state, $entity ) {

		$current_state_diff = array_diff( $current_state, $previous_state );

		if ( count( $current_state_diff ) > 0 ) {

			update_option( "mwp_previous_{$entity}_state", $current_state );

			$auto_update = (array) get_option( "auto_update_{$entity}", array() );
			$auto_update = array_unique( array_merge( $auto_update, $current_state_diff ) );

			update_option( "auto_update_{$entity}", $auto_update );

		}
	}

	private function set_auto_update_for_customer() {

		add_action( 'mwp_schedule_plugins_and_themes', [ $this, 'schedule_plugins_and_themes_migration' ] );

		if (! get_option('mwp_auto_updates_status', false) &&
			( ! get_option( 'mwp_previous_plugins_state', false ) ||
			  ! get_option( 'mwp_auto_updates_status' , false) )) {

				add_option( 'mwp_auto_updates_status', 'enabled' );
				add_action( 'init', [ $this, 'migrate_old_plugins_and_themes' ] );
				// For anything that is drop in, schedule hook for migration
				wp_schedule_single_event( time() + 3600, 'mwp_schedule_plugins_and_themes' );
		}
	}

	public function schedule_plugins_and_themes_migration() {
		if ( ! get_option('mwp_auto_updates_status', false) ) {
			add_option( 'mwp_auto_updates_status', 'enabled' );
		}
		if ( get_option('mwp_auto_updates_status') == 'enabled') {
			$this->migrate_old_plugins_and_themes();
		}
	}

	/**
	 *
	 * Return a nonced auto updates url.
	 *
	 * @param $auto_updates_status
	 *
	 * @return string
	 */
	public static function get_toggle_auto_update_url( $auto_updates_status ) {

		return esc_url(
			add_query_arg(
				[
					'wpaas_auto_updates_status' => $auto_updates_status,
					'wpaas_action'              => 'toggle_auto_update',
					'wpaas_nonce'               => wp_create_nonce( 'wpaas_toggle_auto_update' ),
				]
			)
		);
	}

	/**
	 * Prevent all nags related to core updates.
	 *
	 * 1. Loop through every possible nag on every possible admin notice hook.
	 * 2. Dynamically add a hook that unhooks a nag from itself (hookception).
	 * 3. Unhook the dynamically-added hook.
	 * 4. Close the closure pointer reference after each iteration.
	 */
	private function unhook_core_update_nags() {

		$hooks = [
			'network_admin_notices', // Multisite
			'user_admin_notices',
			'admin_notices',
			'all_admin_notices',
		];

		$callbacks = [
			'update_nag',
			'maintenance_nag',
			'site_admin_notice', // Multisite
		];

		foreach ( $hooks as $hook ) {

			foreach ( $callbacks as $callback ) {

				$closure = function () use ( $hook, $callback, &$closure ) {

					$priority = has_action( $hook, $callback );

					if ( false !== $priority ) {

						remove_action( $hook, $callback, $priority );

					}

					remove_action( $hook, $closure, -PHP_INT_MAX );

				};

				add_action( $hook, $closure, -PHP_INT_MAX );

				unset( $closure ); // Close pointer reference

			}

		}

	}

	/**
	 * Checks if update results array contains any failed plugin/theme. If it does,returns false to signalize not to send update mail.
	 *
	 * @param $enabled
	 * @param array $update_results
	 *
	 * @return bool
	 */
	public function check_theme_plugin_updates_failed( $enabled, $update_results = array() ) {
		foreach ( $update_results as $update_result ) {
			if ( true !== $update_result->result ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Add 6.1 theme to auto update list
	 *
	 * @return void
	 */
	private function migrate_twentytwentythree(){
		if ( get_option('mwp_auto_updates_status') == 'enabled' &&
		     ! get_option('wpaas_migrate_twentytwentythree', false) ) {
			$previous_state = (array) get_option( 'mwp_previous_themes_state', array() );
			$this->check_state(array_merge(['twentytwentythree'], $previous_state), $previous_state,'themes');
			add_option( 'wpaas_migrate_twentytwentythree', true );
		}
	}

}

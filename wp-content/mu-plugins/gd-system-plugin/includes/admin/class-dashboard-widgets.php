<?php

namespace WPaaS\Admin;

use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Dashboard_Widgets {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'wp_dashboard_setup', [ $this, 'init' ] );

	}

	/**
	 * Register custom widgets.
	 *
	 * @action wp_dashboard_setup
	 */
	public function init() {

		/**
		 * Filter the user cap required to view the dashboard widgets.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$cap = (string) apply_filters( 'wpaas_admin_dashboard_widgets_cap', 'activate_plugins' );

		/**
		 * Filter whether dashboard widgets are enabled.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		$enabled = (bool) apply_filters( 'wpaas_admin_dashboard_widgets_enabled', true );

		if ( ! current_user_can( $cap ) || ! $enabled ) {

			return;

		}

		$response = wp_check_php_version();

		if ( $response && isset( $response['is_acceptable'] ) && ! $response['is_acceptable'] && current_user_can( 'activate_plugins' ) ) {

			add_filter( 'postbox_classes_dashboard_dashboard_php_nag', 'dashboard_php_nag_class' );

			wp_add_dashboard_widget( 'dashboard_php_nag', __( 'PHP Update Required' ), 'wp_dashboard_php_nag' );

		}

	}

}

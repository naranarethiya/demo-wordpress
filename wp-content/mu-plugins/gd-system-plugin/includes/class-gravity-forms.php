<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Gravity_Forms {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( ! defined( 'GRAVITY_MANAGER_URL' ) && ! Plugin::is_env( 'prod' ) ) {

			define( 'GRAVITY_MANAGER_URL', 'http://dev.gravityhelp.com/wp-content/plugins/gravitymanager' );

		}

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 0 );


	}

	/**
	 * Special behavior to run early on `plugins_loaded`.
	 *
	 * @action plugins_loaded - 0
	 */
	public function plugins_loaded() {

		if ( ! class_exists( 'GFForms' ) || ! defined( 'GD_GF_LICENSE_KEY' ) || defined( 'GF_LICENSE_KEY' ) || ! get_option( 'gform_pending_installation', true ) ) {

			return;

		}

		define( 'GF_LICENSE_KEY', GD_GF_LICENSE_KEY );

	}

}

<?php

namespace Wpsec\captcha\handlers;

use WP_Error;
use Wpsec\captcha\events\AuthenticateEvent;
use Wpsec\captcha\events\WPLoginFailedEvent;
use Wpsec\captcha\utils\IPUtil;
use Wpsec\captcha\utils\SiteUtil;

class WPLoginEventHandler extends EventHandler {

	private static $event_map = array(
		WPLoginFailedEvent::NAME => 'failed_login',
	);

	/**
	 * Handles comment hooks
	 *
	 * @param   string    $username Entered username
	 * @param   WP_Error $error    WP login error
	 * @since   1.0.0
	 */
	public function handle_login_hook( $username, $error ) {
		$current_hook_name = current_action();

		if ( ! $current_hook_name || ! isset( self::$event_map[ $current_hook_name ] ) ) {
			return;
		}

		if ( isset( $error->errors[ AuthenticateEvent::WP_ERROR_CODE ] ) ) {
			return;
		}

		$this->send_event( self::$event_map[ $current_hook_name ], IPUtil::get_client_ip_headers() );
	}
}

<?php

namespace Wpsec\captcha\handlers;

use WP_Error;
use WP_User;
use Wpsec\captcha\events\AuthenticateEvent;
use Wpsec\captcha\service\CaptchaService;
use Wpsec\captcha\utils\IPUtil;
use Wpsec\captcha\utils\Logger;
use Wpsec\captcha\utils\SiteUtil;

class AuthenticateEventHandler extends EventHandler {

	private static $event_map = array(
		AuthenticateEvent::NAME => 'login',
	);

	const CREDENTIAL_ERRORS = array(
		'empty_username',
		'empty_password',
		'invalid_username',
		'incorrect_password',
	);

	/**
	 * Handles authenticate hook
	 *
	 * @param null|WP_User|WP_Error $user - WP_User if the user is authenticated. WP_Error or null otherwise.
	 *
	 * @return null|WP_User|WP_Error
	 *
	 * @since   1.0.0
	 */
	public function handle_authenticate_hook( $user ) {

		$current_hook_name = current_action();

		if ( empty( $user ) || $this->has_incorrect_credentials( $user ) || ! $current_hook_name || ! isset( self::$event_map[ $current_hook_name ] ) ) {
			return $user;
		}

		if ( ! $this->is_authenticate_from_wp_login() ) {
			return $user;
		}

		$meta_data = array(
			'captcha_id'     => isset( $_POST['wpsec_captcha_id'] ) ? $_POST['wpsec_captcha_id'] : '',
			'captcha_answer' => isset( $_POST['wpsec_captcha_answer'] ) ? $_POST['wpsec_captcha_answer'] : '',
		);

		$response = $this->send_event( self::$event_map[ $current_hook_name ], IPUtil::get_client_ip_headers(), $meta_data );

		$captcha_service = new CaptchaService();

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 204 !== $status_code && $status_code < 500 && $captcha_service->is_wpsec_login_captcha_enabled() ) {
			/* translators: %s: search term */
			$error_message = sprintf( esc_html__( '%1$sError%2$s: Incorrect CAPTCHA. Please try again.', 'wpsec-wp-cp' ), '<strong>', '</strong>' );
			return new WP_Error( AuthenticateEvent::WP_ERROR_CODE, $error_message );
		}

		return $user;
	}


	/**
	 * Checks if authenticate hook has come from wp-login.php page.
	 *
	 * @return bool
	 */
	private function is_authenticate_from_wp_login() {

		$backtrace = debug_backtrace();

		foreach ( $backtrace as $trace ) {
			if ( isset( $trace['file'] ) && str_contains( $trace['file'], 'wp-login.php' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user entered incorrect credentials (checking for flags since validation is already handled by WordPress)
	 *
	 * @param null|WP_User|WP_Error $user - WP_User if the user is authenticated. WP_Error or null otherwise.
	 *
	 * @return bool
	 *
	 * @since   1.0.0
	 */
	private function has_incorrect_credentials( $user ) {
		if ( ! is_wp_error( $user ) ) {
			return false;
		}

		foreach ( self::CREDENTIAL_ERRORS as $credential_error ) {
			if ( isset( $user->errors[ $credential_error ] ) ) {
				Logger::log(
					'User credential error',
					array(
						'captcha_id'       => isset( $_POST['wpsec_captcha_id'] ) ? $_POST['wpsec_captcha_id'] : '',
						'credential_error' => $user->errors[ $credential_error ],
					)
				);
				return true;
			}
		}

		return false;
	}
}

<?php

namespace Wpsec\captcha\handlers;

use WP_Error;
use Wpsec\captcha\service\CaptchaService;
use Wpsec\captcha\utils\Logger;

/**
 * Handles WP hooks
 *
 * @since      1.0.0
 * @package    Wpsec
 * @subpackage Wpsec/Handlers
 */
abstract class EventHandler {

	/**
	 * Captcha trigger point from comment.
	 */
	const COMMENT_TRIGGER_POINT = 'comment';

	/**
	 * Captcha trigger point from login.
	 */
	const WP_LOGIN_TRIGGER_POINT = 'wp_login';

	/**
	 * Captcha Service
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      CaptchaService captcha_service
	 */
	public $captcha_service;

	public function __construct() {
		$this->captcha_service = new CaptchaService();
	}

	/**
	 * Send event type with meta data to the API
	 *
	 * @param   string  $event_type
	 * @param   array   $client_ips
	 * @param   array   $event_meta
	 *
	 * @return array|WP_Error
	 *
	 * @since   1.0.0
	 */
	public function send_event( $event_type, $client_ips, $event_meta = array() ) {
		Logger::log(
			'Send event to API' . ':' . $event_type,
			array(
				'event_type' => $event_type,
				'event_meta' => $event_meta,
				'client_ips' => $client_ips,
			)
		);
		$response = $this->captcha_service->send_event_to_api( $event_type, $client_ips, $event_meta );

		Logger::log(
			'Send event to API Response' . ':' . $event_type,
			array(
				'response' => $response,
			)
		);

		return $response;
	}

	/**
	 * Send event type with meta data to the API
	 *
	 * @since   1.0.0
	 */
	public function show_captcha( $trigger = null ) {
		$this->captcha_service->render_captcha_html_wrapper( $trigger );
	}
}

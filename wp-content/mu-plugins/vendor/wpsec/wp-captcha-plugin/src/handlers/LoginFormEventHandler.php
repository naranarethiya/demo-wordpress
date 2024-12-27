<?php

namespace Wpsec\captcha\handlers;

class LoginFormEventHandler extends EventHandler {

	/**
	 * Handles login form hook
	 *
	 * @since   1.0.0
	 */
	public function handle_login_form_hook() {
		if ( $this->captcha_service->is_wpsec_login_captcha_enabled() ) {
			$this->show_captcha( self::WP_LOGIN_TRIGGER_POINT );
		}
	}
}

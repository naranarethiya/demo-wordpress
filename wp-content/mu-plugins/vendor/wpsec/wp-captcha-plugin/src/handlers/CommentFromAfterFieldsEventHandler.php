<?php

namespace Wpsec\captcha\handlers;

class CommentFromAfterFieldsEventHandler extends EventHandler {

	/**
	 * Handles comment form after fields hook
	 *
	 * @since   1.0.0
	 */

	public function handle_comment_form_hook() {
		if ( $this->captcha_service->is_wpsec_comment_captcha_enabled() ) {
			$this->show_captcha( self::COMMENT_TRIGGER_POINT );
		}
	}
}

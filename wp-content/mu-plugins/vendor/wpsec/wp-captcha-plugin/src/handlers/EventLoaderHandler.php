<?php

namespace Wpsec\captcha\handlers;

use Wpsec\captcha\events\AuthenticateEvent;
use Wpsec\captcha\events\CommentFormAfterFieldsEvent;
use Wpsec\captcha\events\LoginFormEvent;
use Wpsec\captcha\events\PluginsLoadedEvent;
use Wpsec\captcha\events\PreCommentApprovedEvent;
use Wpsec\captcha\events\SpamCommentEvent;
use Wpsec\captcha\events\UnSpamCommentEvent;
use Wpsec\captcha\events\WPLoginFailedEvent;

/**
 * Loads Events
 *
 * @since      1.0.0
 * @package    Wpsec
 * @subpackage Wpsec/Handlers
 */
class EventLoaderHandler {

	/**
	 * Load Events
	 *
	 * @since    1.0.0
	 */
	public function load_events() {
		foreach ( $this->get_events() as $event ) {
			$event::load_event();
		}
	}

	/**
	 * Returns array of events
	 *
	 * @since    1.0.0
	 */
	private function get_events() {
		return array(
			new SpamCommentEvent(),
			new UnSpamCommentEvent(),
			new PreCommentApprovedEvent(),
			new WPLoginFailedEvent(),
			new CommentFormAfterFieldsEvent(),
			new AuthenticateEvent(),
			new LoginFormEvent(),
			new PluginsLoadedEvent(),
		);
	}
}

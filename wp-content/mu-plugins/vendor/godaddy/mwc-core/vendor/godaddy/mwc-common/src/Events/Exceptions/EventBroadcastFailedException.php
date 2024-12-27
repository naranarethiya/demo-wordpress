<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Exception to report a failure transforming an event to Sentry.
 */
class EventBroadcastFailedException extends SentryException
{
}

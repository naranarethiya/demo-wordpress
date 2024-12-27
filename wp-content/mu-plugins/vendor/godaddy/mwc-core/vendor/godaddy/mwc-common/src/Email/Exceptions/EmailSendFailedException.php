<?php

namespace GoDaddy\WordPress\MWC\Common\Email\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Exception to report to Sentry that an email couldn't be sent.
 */
class EmailSendFailedException extends SentryException
{
}

<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Sentry Exception that indicates that there was an error trying to use one of the Auth providers.
 */
class AuthProviderException extends SentryException
{
}

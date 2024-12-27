<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * An exception to be thrown when an email notification isn't available.
 *
 * @see \GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotification::isAvailable()
 */
class EmailNotificationNotAvailableException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 400;
}

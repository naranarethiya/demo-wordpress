<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * An exception to be thrown for a missing webhook row ID.
 */
class InvalidWebhookRowIdException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 404;

    public function __construct(string $message = 'Invalid webhook row ID.', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

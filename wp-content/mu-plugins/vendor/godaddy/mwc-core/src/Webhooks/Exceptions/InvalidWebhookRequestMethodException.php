<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * Exception thrown when an incoming webhook requests is using an invalid method.
 */
class InvalidWebhookRequestMethodException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 405;

    public function __construct(string $message = 'Method Not Allowed', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

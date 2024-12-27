<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * Exception thrown when the webhook namespace is not a valid webhook handler (ex. doesn't implement `WebhookHandlerContract`).
 */
class InvalidWebhookHandlerException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 500;

    public function __construct(string $message = 'Invalid Namespace', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * Exception thrown when an invalid webhook namespace is provided.
 */
class InvalidWebhookNamespaceException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 404;

    public function __construct(string $message = 'Invalid Namespace', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

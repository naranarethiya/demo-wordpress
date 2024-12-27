<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

/**
 * Exception that is thrown when we encounter a `NOT_UNIQUE_ERROR` when executing an API request.
 */
class NotUniqueException extends GatewayRequestException
{
    protected string $errorCode = 'COMMERCE_NOT_UNIQUE_EXCEPTION';
}

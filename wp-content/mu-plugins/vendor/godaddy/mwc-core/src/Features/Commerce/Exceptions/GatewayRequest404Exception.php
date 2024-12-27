<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

/**
 * Exception thrown when the gateway API request returns a 404 status code.
 */
class GatewayRequest404Exception extends GatewayRequestException
{
    protected $code = 404;
}

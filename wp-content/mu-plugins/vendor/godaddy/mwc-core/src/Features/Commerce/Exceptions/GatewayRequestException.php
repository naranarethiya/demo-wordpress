<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

class GatewayRequestException extends BaseException implements CommerceExceptionContract
{
    protected string $errorCode = 'GATEWAY_REQUEST_EXCEPTION';

    use IsCommerceExceptionTrait;
}

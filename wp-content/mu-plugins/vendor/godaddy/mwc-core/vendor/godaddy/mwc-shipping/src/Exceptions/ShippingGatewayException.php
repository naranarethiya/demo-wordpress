<?php

namespace GoDaddy\WordPress\MWC\Shipping\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

class ShippingGatewayException extends BaseException implements ShippingExceptionContract
{
}

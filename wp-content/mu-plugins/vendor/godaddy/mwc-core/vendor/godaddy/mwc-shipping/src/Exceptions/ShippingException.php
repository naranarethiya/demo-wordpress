<?php

namespace GoDaddy\WordPress\MWC\Shipping\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

class ShippingException extends SentryException implements ShippingExceptionContract
{
    /** @var string exception code */
    protected $errorCode = 'SHIPPING_ERROR';

    public function getErrorCode() : string
    {
        return $this->errorCode;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

/**
 * Allows classes to adapt requests responses.
 */
trait AdaptsRequestsTrait
{
    /**
     * Preforms the request and returns the adapted response.
     *
     * @param GatewayRequestAdapterContract $adapter
     * @return mixed
     * @throws ShippingExceptionContract
     */
    abstract protected function doAdaptedRequest(GatewayRequestAdapterContract $adapter);
}

<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface HasShippingRatesGatewayContract
{
    /**
     * Retrieve shipping rates gateway.
     *
     * @return GatewayContract
     */
    public function rates() : GatewayContract;
}

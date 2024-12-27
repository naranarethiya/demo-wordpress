<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface HasShippingCarriersGatewayContract
{
    /**
     * Retrieves carriers.
     *
     * @return GatewayContract
     */
    public function carriers() : GatewayContract;
}

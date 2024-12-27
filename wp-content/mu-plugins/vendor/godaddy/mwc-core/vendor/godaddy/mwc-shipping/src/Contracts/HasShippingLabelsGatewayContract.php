<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface HasShippingLabelsGatewayContract
{
    /**
     * Retrieves shipping labels gateway.
     *
     * @return GatewayContract
     */
    public function labels() : GatewayContract;
}

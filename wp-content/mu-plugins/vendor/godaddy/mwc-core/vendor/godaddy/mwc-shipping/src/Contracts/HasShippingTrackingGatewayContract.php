<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface HasShippingTrackingGatewayContract
{
    /**
     * Provides the tracking gateway.
     *
     * @return GatewayContract
     */
    public function tracking() : GatewayContract;
}

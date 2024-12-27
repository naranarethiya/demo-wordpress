<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface HasShippingAccountsGatewayContract
{
    /**
     * Retrieves accounts.
     *
     * @return GatewayContract
     */
    public function accounts() : GatewayContract;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts;

interface HasOrdersGatewayContract
{
    /**
     * Gets instance of the Orders gateway.
     *
     * @return OrdersGatewayContract
     */
    public function orders() : OrdersGatewayContract;
}

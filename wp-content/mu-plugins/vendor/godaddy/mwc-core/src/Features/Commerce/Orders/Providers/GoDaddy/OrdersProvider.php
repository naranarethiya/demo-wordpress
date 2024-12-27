<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts\OrdersGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts\OrdersProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Gateways\OrdersGateway;

class OrdersProvider implements OrdersProviderContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function orders() : OrdersGatewayContract
    {
        return OrdersGateway::getNewInstance();
    }
}

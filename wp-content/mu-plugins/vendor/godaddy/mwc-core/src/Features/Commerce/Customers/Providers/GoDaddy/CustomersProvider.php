<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts\CustomersGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts\CustomersProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Gateways\CustomersGateway;

class CustomersProvider implements CustomersProviderContract
{
    use CanGetNewInstanceTrait;

    /**
     * Gets an instance of customers gateway.
     *
     * @return CustomersGatewayContract
     */
    public function customers() : CustomersGatewayContract
    {
        return CustomersGateway::getNewInstance();
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanConnectShippingAccountContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\CanDisconnectShippingAccountContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\CanGetDashboardUrlContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

interface ShippingAccountsGatewayContract extends
    GatewayContract,
    CanConnectShippingAccountContract,
    CanDisconnectShippingAccountContract,
    CanGetDashboardUrlContract
{
}

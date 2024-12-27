<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanCalculateShippingRatesContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\CanGetShippingRatesContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

interface ShippingRatesGatewayContract extends GatewayContract, CanCalculateShippingRatesContract, CanGetShippingRatesContract
{
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanListShippingCarriersContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

interface ShippingCarriersGatewayContract extends CanListShippingCarriersContract, GatewayContract
{
}

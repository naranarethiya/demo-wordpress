<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanGetTrackingStatusContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

interface ShippingTrackingGatewayContract extends GatewayContract, CanGetTrackingStatusContract
{
}

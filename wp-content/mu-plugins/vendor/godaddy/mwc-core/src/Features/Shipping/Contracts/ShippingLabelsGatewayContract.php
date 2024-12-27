<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanPurchaseShippingLabelsContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\CanVoidShippingLabelsContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

interface ShippingLabelsGatewayContract extends GatewayContract, CanVoidShippingLabelsContract, CanPurchaseShippingLabelsContract
{
}

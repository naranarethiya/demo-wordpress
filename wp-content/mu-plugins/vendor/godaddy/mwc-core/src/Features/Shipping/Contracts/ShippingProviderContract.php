<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\HasShippingAccountsGatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\HasShippingCarriersGatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\HasShippingLabelsGatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\HasShippingRatesGatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\HasShippingTrackingGatewayContract;

/**
 * @method ShippingAccountsGatewayContract accounts()
 * @method ShippingCarriersGatewayContract carriers()
 * @method ShippingRatesGatewayContract rates()
 * @method ShippingLabelsGatewayContract labels()
 * @method ShippingTrackingGatewayContract tracking()
 */
interface ShippingProviderContract extends
    HasShippingAccountsGatewayContract,
    HasShippingCarriersGatewayContract,
    HasShippingRatesGatewayContract,
    HasShippingLabelsGatewayContract,
    HasShippingTrackingGatewayContract,
    ProviderContract
{
}

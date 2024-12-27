<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\HasShippingCarriersGatewayContract;

/**
 * Can be used to fulfill {@see HasShippingCarriersGatewayContract} on a subclass of {@see ProviderContract}.
 */
trait HasShippingCarriersGatewayTrait
{
    /** @var class-string<GatewayContract> */
    protected $carriersGateway;

    /**
     * Gets an instance of the shipping carriers gateway.
     *
     * @return GatewayContract
     */
    public function carriers() : GatewayContract
    {
        return new $this->carriersGateway();
    }
}

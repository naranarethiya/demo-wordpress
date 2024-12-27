<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

trait HasShippingAccountsGatewayTrait
{
    /** @var class-string<GatewayContract> */
    protected $accountsGateway;

    /**
     * Gets the accounts' gateway.
     *
     * @return GatewayContract
     */
    public function accounts() : GatewayContract
    {
        return new $this->accountsGateway;
    }
}

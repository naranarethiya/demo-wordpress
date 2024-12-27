<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanConnectShippingAccountContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;

/**
 * Can be used to fulfill {@see CanConnectShippingAccountContract} on a subclass of {@see AbstractGateway}.
 */
trait CanConnectShippingAccountTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> */
    protected $connectAccountRequestAdapter;

    /**
     * Connects to given shipping account.
     *
     * @param AccountContract $account
     * @return AccountContract
     * @throws ShippingExceptionContract
     */
    public function connect(AccountContract $account) : AccountContract
    {
        return $this->doAdaptedRequest(new $this->connectAccountRequestAdapter($account));
    }
}

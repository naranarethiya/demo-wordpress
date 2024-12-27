<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanDisconnectShippingAccountContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;

/**
 * Can be used to fulfill {@see CanDisconnectShippingAccountContract} on a subclass of {@see AbstractGateway}.
 */
trait CanDisconnectShippingAccountTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> */
    protected $disconnectAccountRequestAdapter;

    /**
     * Disconnects from given shipping account.
     *
     * @param AccountContract $account
     * @return AccountContract
     * @throws ShippingExceptionContract
     */
    public function disconnect(AccountContract $account) : AccountContract
    {
        return $this->doAdaptedRequest(new $this->disconnectAccountRequestAdapter($account));
    }
}

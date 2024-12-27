<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanGetShippingRatesContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GetShippingRateOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

/**
 * Can be used to fulfill {@see CanGetShippingRatesContract} on a subclass of {@see AbstractGateway}.
 */
trait CanGetShippingRatesTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> */
    protected $getShippingRateRequestAdapter;

    /**
     * Connects to given shipping account.
     *
     * @param GetShippingRateOperationContract $operation
     * @return GetShippingRateOperationContract
     * @throws ShippingExceptionContract
     */
    public function get(GetShippingRateOperationContract $operation) : GetShippingRateOperationContract
    {
        return $this->doAdaptedRequest(new $this->getShippingRateRequestAdapter($operation));
    }
}

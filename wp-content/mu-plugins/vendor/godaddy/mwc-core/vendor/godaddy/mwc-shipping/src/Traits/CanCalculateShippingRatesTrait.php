<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CalculateShippingRatesOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

trait CanCalculateShippingRatesTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> */
    protected $calculateShippingRatesRequestAdapter;

    /**
     * Calculates shipping rates.
     *
     * @param CalculateShippingRatesOperationContract $operation
     *
     * @return CalculateShippingRatesOperationContract
     * @throws ShippingExceptionContract
     */
    public function calculate(CalculateShippingRatesOperationContract $operation) : CalculateShippingRatesOperationContract
    {
        return $this->doAdaptedRequest(new $this->calculateShippingRatesRequestAdapter($operation));
    }
}

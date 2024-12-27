<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface CanGetShippingRatesContract
{
    /**
     * Gets the shipping rate based on the given ID.
     *
     * @param GetShippingRateOperationContract $operation
     * @return GetShippingRateOperationContract
     */
    public function get(GetShippingRateOperationContract $operation) : GetShippingRateOperationContract;
}

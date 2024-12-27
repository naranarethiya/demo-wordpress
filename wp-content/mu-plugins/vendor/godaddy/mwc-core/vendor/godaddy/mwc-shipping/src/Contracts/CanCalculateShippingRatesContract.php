<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface CanCalculateShippingRatesContract
{
    /**
     * Calculates shipping rates.
     *
     * @param CalculateShippingRatesOperationContract $operation
     * @return CalculateShippingRatesOperationContract
     */
    public function calculate(CalculateShippingRatesOperationContract $operation) : CalculateShippingRatesOperationContract;
}

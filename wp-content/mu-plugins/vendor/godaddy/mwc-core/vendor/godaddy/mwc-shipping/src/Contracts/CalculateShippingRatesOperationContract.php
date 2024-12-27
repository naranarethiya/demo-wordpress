<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;

interface CalculateShippingRatesOperationContract extends OperationContract, HasAccountContract, HasShipmentContract
{
    /**
     * Gets shipping carriers.
     *
     * @return CarrierContract[]
     */
    public function getCarriers() : array;

    /**
     * Sets shipping carriers.
     *
     * @param CarrierContract ...$value
     * @return $this
     */
    public function setCarriers(CarrierContract ...$value);

    /**
     * Gets shipping rates.
     *
     * @return ShippingRateContract[]
     */
    public function getShippingRates() : array;

    /**
     * Sets shipping rates.
     *
     * @param ShippingRateContract ...$value
     * @return $this
     */
    public function setShippingRates(ShippingRateContract ...$value);
}

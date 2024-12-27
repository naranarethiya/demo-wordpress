<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;

interface GetShippingRateOperationContract extends OperationContract, HasAccountContract
{
    /**
     * Get shipping rate ID.
     *
     * @return string
     */
    public function getShippingRateId() : string;

    /**
     * Sets shipping rate ID.
     *
     * @param string $value
     * @return $this
     */
    public function setShippingRateId(string $value);

    /**
     * Get shipping rate object.
     *
     * @return ShippingRateContract
     */
    public function getShippingRate() : ShippingRateContract;

    /**
     * Sets shipping rate object.
     *
     * @param ShippingRateContract $value
     * @return $this
     */
    public function setShippingRate(ShippingRateContract $value);
}

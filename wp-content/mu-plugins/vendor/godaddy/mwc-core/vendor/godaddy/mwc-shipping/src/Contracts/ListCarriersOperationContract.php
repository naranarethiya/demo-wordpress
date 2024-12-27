<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;

interface ListCarriersOperationContract extends OperationContract, HasAccountContract
{
    /**
     * Gets the carriers for this operation.
     * @return CarrierContract[]
     */
    public function getCarriers() : array;

    /**
     * Sets the carriers for this operation.
     *
     * @param CarrierContract... $value
     * @return $this
     */
    public function setCarriers(CarrierContract ...$value);
}

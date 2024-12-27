<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

/**
 * HasShipment Contract.
 */
interface HasShipmentContract
{
    /**
     * Gets the ShipmentContract object.
     *
     * @return ShipmentContract
     */
    public function getShipment() : ShipmentContract;

    /**
     * Sets the ShipmentContract object.
     *
     * @param ShipmentContract $value
     * @return $this
     */
    public function setShipment(ShipmentContract $value);
}

<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;

trait HasShipmentTrait
{
    /** @var ShipmentContract */
    protected $shipment;

    /**
     * Gets shipment.
     *
     * @return ShipmentContract
     */
    public function getShipment() : ShipmentContract
    {
        return $this->shipment;
    }

    /**
     * Sets shipment.
     *
     * @param ShipmentContract $value
     * @return $this
     */
    public function setShipment(ShipmentContract $value)
    {
        $this->shipment = $value;

        return $this;
    }
}

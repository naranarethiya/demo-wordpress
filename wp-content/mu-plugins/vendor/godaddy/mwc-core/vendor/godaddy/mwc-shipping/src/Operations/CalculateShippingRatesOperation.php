<?php

namespace GoDaddy\WordPress\MWC\Shipping\Operations;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CalculateShippingRatesOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasAccountTrait;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasShipmentTrait;

class CalculateShippingRatesOperation implements CalculateShippingRatesOperationContract
{
    use HasAccountTrait;
    use HasShipmentTrait;

    /** @var CarrierContract[] */
    protected $carriers;

    /** @var ShippingRateContract[] */
    protected $shippingRates;

    /**
     * {@inheritDoc}
     */
    public function getCarriers() : array
    {
        return $this->carriers;
    }

    /**
     * {@inheritDoc}
     */
    public function setCarriers(CarrierContract ...$value)
    {
        $this->carriers = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingRates() : array
    {
        return $this->shippingRates;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingRates(ShippingRateContract ...$value)
    {
        $this->shippingRates = $value;

        return $this;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Shipping\Operations;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GetShippingRateOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasAccountTrait;

class GetShippingRateOperation implements GetShippingRateOperationContract
{
    use HasAccountTrait;

    /** @var string */
    protected $shippingRateId;

    /** @var ShippingRateContract */
    protected $shippingRate;

    /**
     * {@inheritDoc}
     */
    public function getShippingRateId() : string
    {
        return $this->shippingRateId;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingRateId(string $value)
    {
        $this->shippingRateId = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingRate() : ShippingRateContract
    {
        return $this->shippingRate;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingRate(ShippingRateContract $value)
    {
        $this->shippingRate = $value;

        return $this;
    }
}

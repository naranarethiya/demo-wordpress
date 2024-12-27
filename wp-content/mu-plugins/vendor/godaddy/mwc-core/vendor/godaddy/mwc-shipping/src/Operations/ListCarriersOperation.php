<?php

namespace GoDaddy\WordPress\MWC\Shipping\Operations;

use GoDaddy\WordPress\MWC\Shipping\Contracts\ListCarriersOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasAccountTrait;

class ListCarriersOperation implements ListCarriersOperationContract
{
    use HasAccountTrait;

    /** @var CarrierContract[] */
    protected $carriers = [];

    /** {@inheritdoc} */
    public function getCarriers() : array
    {
        return $this->carriers;
    }

    /** {@inheritdoc} */
    public function setCarriers(CarrierContract ...$value)
    {
        $this->carriers = $value;

        return $this;
    }
}

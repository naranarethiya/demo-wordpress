<?php

namespace GoDaddy\WordPress\MWC\Shipping\Operations;

use GoDaddy\WordPress\MWC\Shipping\Contracts\PurchaseShippingLabelsOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasAccountTrait;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasShipmentTrait;

class PurchaseShippingLabelsOperation implements PurchaseShippingLabelsOperationContract
{
    use HasAccountTrait;
    use HasShipmentTrait;

    /** @var string */
    protected $layout;

    /** @var string */
    protected $format;

    /**
     * {@inheritDoc}
     */
    public function getLayout() : string
    {
        return $this->layout;
    }

    /**
     * {@inheritDoc}
     */
    public function setLayout(string $value)
    {
        $this->layout = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormat() : string
    {
        return $this->format;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormat(string $value)
    {
        $this->format = $value;

        return $this;
    }
}

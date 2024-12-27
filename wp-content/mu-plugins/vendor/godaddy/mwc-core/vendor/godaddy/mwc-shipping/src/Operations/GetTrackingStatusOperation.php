<?php

namespace GoDaddy\WordPress\MWC\Shipping\Operations;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GetTrackingStatusOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasAccountTrait;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasPackageTrait;

class GetTrackingStatusOperation implements GetTrackingStatusOperationContract
{
    use HasAccountTrait;
    use HasPackageTrait;

    /** @var string */
    protected $trackingNumber;

    /** @var string */
    protected $trackingUrl;

    /**
     * {@inheritDoc}
     */
    public function setTrackingNumber(string $trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingNumber() : string
    {
        return $this->trackingNumber;
    }

    /**
     * {@inheritDoc}
     */
    public function setTrackingUrl(string $trackingUrl)
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingUrl() : string
    {
        return $this->trackingUrl;
    }
}

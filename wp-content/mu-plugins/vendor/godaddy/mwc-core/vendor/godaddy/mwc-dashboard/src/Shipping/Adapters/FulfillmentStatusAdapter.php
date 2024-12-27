<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\PartiallyFulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\UnfulfilledFulfillmentStatus;

abstract class FulfillmentStatusAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var string the fulfillment status meta key */
    const META_KEY = '_mwc_fulfillment_status';

    /**
     * {@inheritDoc}
     */
    abstract public function convertFromSource();

    /**
     * {@inheritDoc}
     */
    abstract public function convertToSource();

    /**
     * Translate the value of the meta entry into one of our FulfillmentStatus classes.
     *
     * @param string $name
     * @return FulfillmentStatusContract
     */
    protected function getFulfillmentStatusByName(string $name) : FulfillmentStatusContract
    {
        $statusList = [
            new FulfilledFulfillmentStatus(),
            new PartiallyFulfilledFulfillmentStatus(),
            new UnfulfilledFulfillmentStatus(),
        ];

        foreach ($statusList as $status) {
            if ($name === $status->getName()) {
                return $status;
            }
        }

        return new UnfulfilledFulfillmentStatus();
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\LineItemStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\PartiallyFulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\UnfulfilledFulfillmentStatus;

class LineItemFulfillmentStatusAdapter implements DataObjectAdapterContract
{
    /**
     * Converts a Commerce's line item fulfillment status into a line item fulfillment status.
     *
     * @param LineItemStatus::* $source
     * @return FulfillmentStatusContract
     */
    public function convertFromSource($source) : FulfillmentStatusContract
    {
        switch ($source) {
            case LineItemStatus::Fulfilled:
                return new FulfilledFulfillmentStatus();
            case LineItemStatus::PartiallyFulfilled:
                return new PartiallyFulfilledFulfillmentStatus();
            case LineItemStatus::Awaiting:
            case LineItemStatus::Canceled:
            case LineItemStatus::Confirmed:
            case LineItemStatus::InProgress:
            case LineItemStatus::OnHold:
            case LineItemStatus::PartiallyReturned:
            case LineItemStatus::Returned:
            case LineItemStatus::Unfulfilled:
            default:
                return new UnfulfilledFulfillmentStatus();
        }
    }

    /**
     * Converts a line item into a Commerce's line item fulfillment status.
     *
     * @param LineItem $target
     * @return LineItemStatus::*
     */
    public function convertToSource($target) : string
    {
        if ($target->getFulfillmentStatus() instanceof FulfilledFulfillmentStatus) {
            return LineItemStatus::Fulfilled;
        }

        if ($target->getFulfillmentStatus() instanceof PartiallyFulfilledFulfillmentStatus) {
            return LineItemStatus::PartiallyFulfilled;
        }

        return LineItemStatus::Unfulfilled;
    }
}

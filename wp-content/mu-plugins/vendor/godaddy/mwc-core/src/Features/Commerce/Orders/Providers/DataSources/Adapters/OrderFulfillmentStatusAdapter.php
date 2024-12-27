<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CompletedOrderStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\FulfillmentStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\PartiallyFulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\UnfulfilledFulfillmentStatus;

class OrderFulfillmentStatusAdapter implements DataObjectAdapterContract
{
    /**
     * Converts a Commerce's order fulfillment status into an order fulfillment status.
     *
     * @param FulfillmentStatus::* $source
     * @return FulfillmentStatusContract
     */
    public function convertFromSource($source) : FulfillmentStatusContract
    {
        if (FulfillmentStatus::Fulfilled === $source) {
            return new FulfilledFulfillmentStatus();
        }

        if (FulfillmentStatus::PartiallyFulfilled === $source) {
            return new PartiallyFulfilledFulfillmentStatus();
        }

        return new UnfulfilledFulfillmentStatus();
    }

    /**
     * Converts an order fulfillment status into a Commerce's order fulfillment status.
     *
     * @param Order $target
     * @return FulfillmentStatus::*
     */
    public function convertToSource($target) : string
    {
        if (! $fulfillmentStatus = $target->getFulfillmentStatus()) {
            return $this->convertOrderStatusToSource($target->getStatus());
        }

        switch (get_class($fulfillmentStatus)) {
            case FulfilledFulfillmentStatus::class:
                return FulfillmentStatus::Fulfilled;

            case PartiallyFulfilledFulfillmentStatus::class:
                return FulfillmentStatus::PartiallyFulfilled;

            default:
                return FulfillmentStatus::Unfulfilled;
        }
    }

    /**
     * Converts an order status into a Commerce's order fulfillment status.
     *
     * @param OrderStatusContract|null $status
     * @return FulfillmentStatus::*
     */
    protected function convertOrderStatusToSource(?OrderStatusContract $status) : string
    {
        return $status instanceof CompletedOrderStatus ?
            FulfillmentStatus::Fulfilled :
            FulfillmentStatus::Unfulfilled;
    }
}

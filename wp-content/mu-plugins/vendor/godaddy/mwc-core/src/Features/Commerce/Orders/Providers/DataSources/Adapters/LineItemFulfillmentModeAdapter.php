<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasOrderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\LineItemMode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasOrderTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\Enums\ShippingMethodId;

class LineItemFulfillmentModeAdapter implements DataObjectAdapterContract, HasOrderContract
{
    use HasOrderTrait;

    /**
     * Converts a Commerce's line item fulfillment mode into order property values.
     *
     * @param LineItemMode::* $source
     * @return array{
     *     isVirtual: bool,
     *     isDownloadable: bool
     * }
     */
    public function convertFromSource($source) : array
    {
        return [
            'isVirtual' => in_array($source, [
                LineItemMode::Digital,
                LineItemMode::GiftCard,
                LineItemMode::Purchase,
                LineItemMode::QuickStay,
                LineItemMode::RegularStay,
            ]),
            'isDownloadable' => in_array($source, [
                LineItemMode::Digital,
                LineItemMode::GiftCard,
            ]),
        ];
    }

    /**
     * Converts a line item into a Commerce's line item fulfillment mode.
     *
     * @param LineItem $target
     * @return LineItemMode::*
     */
    public function convertToSource($target) : string
    {
        if ($target->getIsDownloadable()) {
            return LineItemMode::Digital;
        }

        if ($target->getIsVirtual()) {
            return LineItemMode::Purchase;
        }

        if ($this->isLocalPickup($target)) {
            return LineItemMode::Pickup;
        }

        if ($this->isLocalDelivery()) {
            return LineItemMode::Delivery;
        }

        return LineItemMode::Ship;
    }

    /**
     * Determines whether the given line item is a pickup item.
     */
    protected function isLocalPickup(LineItem $lineItem) : bool
    {
        // if the line item has a channel ID, which represents a pickup location, consider it a pickup item
        if ($lineItem->getFulfillmentChannelId()) {
            return true;
        }

        // consider the line item a pickup item if the order is using a local pickup shipping method
        if ($order = $this->getOrder()) {
            return $order->hasShippingMethod([ShippingMethodId::LocalPickup, ShippingMethodId::LocalPickupPlus]);
        }

        return false;
    }

    /**
     * Determines whether the given order is using the local delivery shipping method.
     */
    protected function isLocalDelivery() : bool
    {
        $order = $this->getOrder();

        return $order && $order->hasShippingMethod(ShippingMethodId::LocalDelivery);
    }
}

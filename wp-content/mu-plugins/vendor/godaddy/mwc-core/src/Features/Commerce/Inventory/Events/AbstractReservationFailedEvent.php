<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

abstract class AbstractReservationFailedEvent extends AbstractInventoryServiceFailEvent
{
    /** @var Order */
    public Order $order;

    /**
     * Event constructor.
     *
     * @param Order $order
     * @param string $failReason
     */
    public function __construct(Order $order, string $failReason)
    {
        $this->order = $order;
        $this->failReason = $failReason;
    }
}

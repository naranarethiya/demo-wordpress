<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class LineItemReservedEvent implements EventContract
{
    public LineItem $lineItem;

    /** @var Reservation[] */
    public array $reservations;

    public Order $order;

    /**
     * @param LineItem $lineItem
     * @param Reservation[] $reservations
     * @param Order $order
     */
    public function __construct(
        LineItem $lineItem,
        array $reservations,
        Order $order
    ) {
        $this->lineItem = $lineItem;
        $this->reservations = $reservations;
        $this->order = $order;
    }
}

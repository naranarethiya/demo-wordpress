<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Contract for creating or updating reservations.
 */
interface CreateOrUpdateReservationOperationContract extends ReservationOperationContract
{
    /**
     * Gets the order.
     *
     * @return Order
     */
    public function getOrder() : Order;
}

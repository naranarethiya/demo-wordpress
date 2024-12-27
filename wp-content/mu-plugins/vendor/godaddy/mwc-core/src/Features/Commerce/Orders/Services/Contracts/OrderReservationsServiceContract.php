<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

interface OrderReservationsServiceContract
{
    /**
     * Creates or updates reservations for each line item in the order.
     *
     * @param Order $order
     */
    public function createOrUpdateReservations(Order &$order) : void;
}

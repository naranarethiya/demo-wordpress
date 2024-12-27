<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class NoopOrderReservationsService implements OrderReservationsServiceContract
{
    /**
     * {@inheritDoc}
     */
    public function createOrUpdateReservations(Order &$order) : void
    {
        // No-op
    }
}

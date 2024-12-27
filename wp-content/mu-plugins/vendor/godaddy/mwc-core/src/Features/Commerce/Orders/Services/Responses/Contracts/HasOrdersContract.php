<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Contract for a component that has an array of {@see Order} models.
 */
interface HasOrdersContract
{
    /**
     * Gets the orders.
     *
     * @return Order[]
     */
    public function getOrders() : array;

    /**
     * Sets the orders.
     *
     * @param Order[] $value
     *
     * @return $this
     */
    public function setOrders(array $value);
}

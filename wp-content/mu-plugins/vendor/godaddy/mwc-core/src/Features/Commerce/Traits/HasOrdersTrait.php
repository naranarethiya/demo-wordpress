<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts\HasOrdersContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * A trait used to fulfill the {@see HasOrdersContract} interface.
 */
trait HasOrdersTrait
{
    /** @var Order[] */
    protected array $orders = [];

    /**
     * Gets the orders.
     *
     * @return Order[]
     */
    public function getOrders() : array
    {
        return $this->orders;
    }

    /**
     * Sets the orders.
     *
     * @param Order[] $value
     *
     * @return $this
     */
    public function setOrders(array $value)
    {
        $this->orders = $value;

        return $this;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

interface CreateOrderOperationContract
{
    /**
     * Gets the order for the operation.
     */
    public function getOrder() : Order;

    /**
     * Sets the order for the operation.
     *
     * @param Order $value
     * @return $this
     */
    public function setOrder(Order $value);
}

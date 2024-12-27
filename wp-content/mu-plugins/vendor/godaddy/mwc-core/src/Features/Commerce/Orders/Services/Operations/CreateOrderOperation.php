<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\CreateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class CreateOrderOperation implements CreateOrderOperationContract
{
    use CanSeedTrait;

    protected Order $order;

    /**
     * {@inheritDoc}
     */
    public function getOrder() : Order
    {
        return $this->order;
    }

    /**
     * Creates an operation object from a given order object.
     *
     * @param Order $order
     *
     * @return static
     */
    public static function fromOrder(Order $order)
    {
        return static::seed(['order' => $order]);
    }

    /**
     * {@inheritDoc}
     */
    public function setOrder(Order $value)
    {
        $this->order = $value;

        return $this;
    }
}

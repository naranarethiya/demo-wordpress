<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Contracts\HasOrderIdContract;

/**
 * A trait for objects that can have an {@see Order} numerical identifier.
 *
 * @see HasOrderIdContract
 */
trait HasOrderIdTrait
{
    /** @var positive-int|null */
    protected ?int $orderId = null;

    /**
     * Gets the order ID associated with this object.
     *
     * @return positive-int|null
     */
    public function getOrderId() : ?int
    {
        return $this->orderId;
    }

    /**
     * Sets the order ID associated with this object.
     *
     * @param int|null $value
     * @return $this
     */
    public function setOrderId(?int $value)
    {
        $this->orderId = $value > 0 ? $value : null;

        return $this;
    }
}

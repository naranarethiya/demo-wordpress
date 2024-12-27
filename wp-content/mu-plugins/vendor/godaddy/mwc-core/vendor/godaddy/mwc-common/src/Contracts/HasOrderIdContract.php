<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * A contract for objects that can have an {@see Order} numerical identifier.
 */
interface HasOrderIdContract
{
    /**
     * Gets the order ID associated with this object.
     *
     * @return positive-int|null
     */
    public function getOrderId() : ?int;

    /**
     * Sets the order ID associated with this object.
     *
     * @param int|null $value The value to set
     * @return $this
     */
    public function setOrderId(?int $value);
}

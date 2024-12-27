<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Address;

/**
 * Shippable Contract.
 */
interface ShippableContract
{
    /**
     * Gets Shipping Address.
     *
     * @return Address
     */
    public function getShippingAddress() : Address;

    /**
     * Sets Shipping Address.
     *
     * @param Address $value
     * @return $this
     */
    public function setShippingAddress(Address $value);
}

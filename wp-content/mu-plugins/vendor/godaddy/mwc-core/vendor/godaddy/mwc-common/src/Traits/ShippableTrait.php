<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Models\Address;

/**
 * A trait for objects that are shippable.
 */
trait ShippableTrait
{
    /** @var Address|null the shipping address */
    protected ?Address $shippingAddress = null;

    /**
     * Gets the shipping address.
     *
     * @return Address
     */
    public function getShippingAddress() : Address
    {
        if (! $shippingAddress = $this->shippingAddress) {
            $this->setShippingAddress($shippingAddress = new Address());
        }

        return $shippingAddress;
    }

    /**
     * Sets the shipping address.
     *
     * @param Address $address
     * @return $this
     */
    public function setShippingAddress(Address $address)
    {
        $this->shippingAddress = $address;

        return $this;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Models\Address;

/**
 * A trait for objects that are billable.
 */
trait BillableTrait
{
    /** @var Address|null the billing address */
    protected ?Address $billingAddress = null;

    /**
     * Gets the billing address.
     *
     * @return Address
     */
    public function getBillingAddress() : Address
    {
        if (! $billingAddress = $this->billingAddress) {
            $this->setBillingAddress($billingAddress = new Address());
        }

        return $billingAddress;
    }

    /**
     * Sets the billing address.
     *
     * @param Address $address
     * @return $this
     */
    public function setBillingAddress(Address $address)
    {
        $this->billingAddress = $address;

        return $this;
    }
}

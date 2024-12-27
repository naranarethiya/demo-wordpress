<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Address;

/**
 * Billable Contract.
 */
interface BillableContract
{
    /**
     * Gets Billing Address.
     *
     * @return Address
     */
    public function getBillingAddress() : Address;

    /**
     * Sets Billing Address.
     *
     * @param Address $value
     * @return $this
     */
    public function setBillingAddress(Address $value);
}

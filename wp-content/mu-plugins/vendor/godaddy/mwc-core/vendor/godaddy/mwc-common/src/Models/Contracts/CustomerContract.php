<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\BillableContract;
use GoDaddy\WordPress\MWC\Common\Contracts\ShippableContract;

interface CustomerContract extends ModelContract, BillableContract, ShippableContract
{
    /**
     * Gets the first name of the customer.
     *
     * @return non-empty-string|null
     */
    public function getFirstName() : ?string;

    /**
     * Sets the first name of the customer.
     *
     * @param non-empty-string|null $value
     * @return $this
     */
    public function setFirstName(?string $value);

    /**
     * Gets the last name of the customer.
     *
     * @return non-empty-string|null
     */
    public function getLastName() : ?string;

    /**
     * Sets the last name of the customer.
     *
     * @param non-empty-string|null $value
     * @return $this
     */
    public function setLastName(?string $value);

    /**
     * Gets the email address of the customer.
     *
     * @return non-empty-string|null
     */
    public function getEmail() : ?string;

    /**
     * Sets the email address of the customer.
     *
     * @param non-empty-string|null $value
     * @return $this
     */
    public function setEmail(?string $value);
}

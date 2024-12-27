<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;

interface CreateOrUpdateCustomerOperationContract
{
    /**
     * Sets the customer model to be created or updated.
     *
     * @param CustomerContract $value
     * @return $this
     */
    public function setCustomer(CustomerContract $value);

    /**
     * Gets the customer model to be created or updated.
     *
     * @return CustomerContract
     */
    public function getCustomer() : CustomerContract;

    /**
     * Sets the customer's local WooCommerce ID.
     *
     * @param int|null $value
     * @return $this
     */
    public function setLocalId(?int $value);

    /**
     * Gets the customer's local WooCommerce ID.
     *
     * @return int|null
     */
    public function getLocalId() : ?int;
}

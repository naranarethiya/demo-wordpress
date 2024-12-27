<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\Contracts\CreateOrUpdateCustomerOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\GuestCustomer;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

class CreateOrUpdateCustomerOperation implements CreateOrUpdateCustomerOperationContract
{
    use CanSeedTrait;

    /** @var CustomerContract the customer model */
    protected CustomerContract $customer;

    /** @var int|null the customer's local WooCommerce ID */
    protected ?int $localId = null;

    /**
     * Create operation object from a given customer object.
     *
     * @param CustomerContract $customer
     *
     * @return static
     */
    public static function fromCustomer(CustomerContract $customer)
    {
        $localId = null;

        if ($customer instanceof GuestCustomer) {
            $localId = $customer->getOrderId();
        } elseif ($customer instanceof Customer) {
            $localId = $customer->getId();
        }

        return static::seed([
            'customer' => $customer,
            'localId'  => $localId,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomer(CustomerContract $value) : CreateOrUpdateCustomerOperation
    {
        $this->customer = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomer() : CustomerContract
    {
        return $this->customer;
    }

    /**
     * {@inheritDoc}
     */
    public function setLocalId(?int $value) : CreateOrUpdateCustomerOperation
    {
        $this->localId = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalId() : ?int
    {
        return $this->localId;
    }
}

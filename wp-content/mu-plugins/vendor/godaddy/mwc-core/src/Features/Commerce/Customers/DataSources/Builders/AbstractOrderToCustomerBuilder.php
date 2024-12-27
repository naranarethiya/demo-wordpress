<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\DataSources\Builders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\GuestCustomer;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use WC_Customer;

/**
 * @template TModel of object
 */
abstract class AbstractOrderToCustomerBuilder
{
    /** @var TModel */
    protected object $order;

    /**
     * Sets the source order for this builder.
     *
     * @param TModel $order
     * @return $this
     */
    public function setOrder(object $order) : AbstractOrderToCustomerBuilder
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Builds a new {@see CustomerContract} instance using order information.
     *
     * @return CustomerContract|null
     */
    public function build() : ?CustomerContract
    {
        if (! $customer = $this->getCustomerFromOrder($this->order)) {
            return null;
        }

        $customerInfoFromOrder = $this->getCustomerInfoFromOrder($this->order);

        $infoProperties = [
            'email',
            'firstName',
            'lastName',
            'billingAddress',
            'shippingAddress',
        ];

        foreach ($infoProperties as $propertyName) {
            $getterMethodName = 'get'.ucfirst($propertyName);
            $setterMethodName = 'set'.ucfirst($propertyName);

            if (! method_exists($customer, $getterMethodName) ||
                ! method_exists($customer, $setterMethodName)) {
                continue;
            }

            if ($value = $this->getInfoValueIfNeedsToBeSet($customerInfoFromOrder, $propertyName, $customer->{$getterMethodName}())) {
                $customer->{$setterMethodName}($value);
            }
        }

        return $customer;
    }

    /**
     * Gets a value from customer info, only if it needs to be set on the customer.
     *
     * @template TValue
     * @param array{
     *     email: string,
     *     firstName: string,
     *     lastName: string,
     *     billingAddress: Address,
     *     shippingAddress: Address,
     * } $customerInfoFromOrder
     * @param non-empty-string $infoKey array key to look up in $customerInfoFromOrder
     * @param TValue $currentValue current value from customer object
     *
     * @return TValue|null value from order info when current value is empty and info value exists, otherwise null
     */
    protected function getInfoValueIfNeedsToBeSet(array $customerInfoFromOrder, string $infoKey, $currentValue)
    {
        if (empty($currentValue) && ! empty($infoValue = ArrayHelper::get($customerInfoFromOrder, $infoKey))) {
            return $infoValue;
        }

        return null;
    }

    /**
     * Gets a customer instance for the given order.
     *
     * @param TModel $order
     * @return CustomerContract|null
     */
    protected function getCustomerFromOrder(object $order) : ?CustomerContract
    {
        $customerId = $this->getCustomerIdFromOrder($order);

        return $customerId > 0 ? $this->getRegisteredCustomer($customerId) : $this->getGuestCustomerFromOrder($order);
    }

    /**
     * Gets a {@see Customer} instance representing the WooCommerce customer with the given ID.
     *
     * @param positive-int $customerId
     * @return Customer|null
     */
    protected function getRegisteredCustomer(int $customerId) : ?Customer
    {
        if (! $wooCustomer = $this->getWooCustomerInstance($customerId)) {
            return null;
        }

        return CustomerAdapter::getNewInstance($wooCustomer)->convertFromSource();
    }

    /**
     * Gets a {@see GuestCustomer} instance associated with the given order.
     *
     * @param TModel $order
     * @return GuestCustomer
     */
    protected function getGuestCustomerFromOrder(object $order) : GuestCustomer
    {
        return (new GuestCustomer())->setOrderId($this->getOrderId($order));
    }

    /**
     * Gets the ID of the registered customer associated with the given order.
     *
     * Returns zero if the order is not associated wiht a registered customer.
     *
     * @param TModel $order
     * @return int
     */
    abstract protected function getCustomerIdFromOrder(object $order) : int;

    /**
     * Gets an instance of {@see WC_Customer} identified with the given ID.
     *
     * Returns null if an error occurs trying to load the customer instance.
     *
     * @param positive-int $customerId
     * @return WC_Customer|null
     */
    protected function getWooCustomerInstance(int $customerId) : ?WC_Customer
    {
        try {
            return $this->instantiateWooCustomer($customerId);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param positive-int $customerId
     *
     * @return WC_Customer
     * @throws Exception
     * @codeCoverageIgnore cannot test newing up WC object
     */
    protected function instantiateWooCustomer(int $customerId) : WC_Customer
    {
        return new WC_Customer($customerId);
    }

    /**
     * Gets the ID of the given order.
     *
     * @param TModel $order
     * @return int
     */
    abstract protected function getOrderId(object $order) : int;

    /**
     * Gets an array of customer information retrieved from the given order.
     *
     * @param TModel $order
     * @return array{
     *     email: string,
     *     firstName: string,
     *     lastName: string,
     *     billingAddress: Address,
     *     shippingAddress: Address,
     * }
     */
    abstract protected function getCustomerInfoFromOrder(object $order);
}

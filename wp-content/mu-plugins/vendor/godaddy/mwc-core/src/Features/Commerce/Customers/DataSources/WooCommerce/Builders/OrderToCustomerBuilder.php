<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\DataSources\WooCommerce\Builders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Builders\Contracts\BuilderContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\GuestCustomer;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use WC_Customer;
use WC_Order;

/**
 * @method static static getNewInstance(WC_Order $wooOrder)
 */
class OrderToCustomerBuilder implements BuilderContract
{
    use CanGetNewInstanceTrait;

    protected WC_Order $wooOrder;

    /**
     * Constructor.
     *
     * @param WC_Order $wooOrder
     */
    public function __construct(WC_Order $wooOrder)
    {
        $this->wooOrder = $wooOrder;
    }

    /**
     * Gets the address from the provided address data.
     *
     * @param mixed $address
     *
     * @return Address
     */
    protected function convertWooAddress($address) : Address
    {
        return AddressAdapter::getNewInstance(ArrayHelper::wrap($address))->convertFromSource();
    }

    /**
     * Attempts to fetch a field from the user data, and falls back to an order field when the user doesn't have the
     * field, or the order doesn't have a user.
     *
     * @param CustomerContract|null $customer       The customer to try and fetch the data
     * @param string                $customerMethod The method to call on the customer object
     * @param string                $fallbackMethod The fallback method to fetch data.
     *
     * @return string|null The value, or null if no value was set.
     */
    protected function getFieldValueWithFallback(?CustomerContract $customer, string $customerMethod, string $fallbackMethod) : ?string
    {
        if ($field = $customer ? $customer->{$customerMethod}() : null) {
            return $field;
        }

        return ((string) $this->wooOrder->{$fallbackMethod}('edit')) ?: null;
    }

    /**
     * Creates an instance of WC_Customer given the provided customer ID.
     *
     * @throws Exception
     */
    protected function createWooCustomerInstance(int $customer_id) : WC_Customer
    {
        return new WC_Customer($customer_id);
    }

    /**
     * Fetches the customer instance from the order.
     *
     * @return WC_Customer|null The customer, if this order has one. Otherwise, null.
     */
    protected function getWooCustomerInstance() : ?WC_Customer
    {
        try {
            return $this->wooOrder->get_customer_id() ? $this->createWooCustomerInstance($this->wooOrder->get_customer_id()) : null;
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return ?CustomerContract
     */
    public function build() : ?CustomerContract
    {
        $userId = TypeHelper::int($this->wooOrder->get_customer_id(), 0);

        if ($userId) {
            $instance = CustomerAdapter::getNewInstance($this->getWooCustomerInstance())->convertFromSource();
        } else {
            $orderId = TypeHelper::int($this->wooOrder->get_id(), 0);
            $instance = (new GuestCustomer())->setOrderId($orderId ?: null);
        }

        if ($email = $this->getFieldValueWithFallback($instance, 'getEmail', 'get_billing_email')) {
            $instance->setEmail($email);
        }

        if ($firstName = $this->getFieldValueWithFallback($instance, 'getFirstName', 'get_billing_first_name')) {
            $instance->setFirstName($firstName);
        }

        if ($lastName = $this->getFieldValueWithFallback($instance, 'getLastName', 'get_billing_last_name')) {
            $instance->setLastName($lastName);
        }

        return $instance
            ->setBillingAddress($this->convertWooAddress($this->wooOrder->get_address()))
            ->setShippingAddress($this->convertWooAddress($this->wooOrder->get_address('shipping')));
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use WC_Customer;

/**
 * Customer adapter.
 *
 * @since 0.1.0
 */
class CustomerAdapter
{
    use CanGetNewInstanceTrait;

    /** @var WC_Customer WooCommerce customer object */
    private $source;

    /**
     * Customer adapter constructor.
     *
     * @since 0.1.0
     *
     * @param WC_Customer $wooCommerceCustomer WooCommerce customer object
     */
    public function __construct(WC_Customer $wooCommerceCustomer)
    {
        $this->source = $wooCommerceCustomer;
    }

    /**
     * Converts a WooCommerce customer into a native customer.
     *
     * @since 0.1.0
     *
     * @return Customer
     */
    public function convertFromSource() : Customer
    {
        $customer = new Customer();
        $customer->setId($this->source->get_id());
        $customer->setRemoteId($this->source->get_meta('mwc_remote_id'));
        $customer->setShippingAddress((new AddressAdapter($this->source->get_shipping()))->convertFromSource());
        $customer->setBillingAddress((new AddressAdapter($this->source->get_billing()))->convertFromSource());

        if ($user = User::get($this->source->get_id())) {
            $customer->setUser($user);
        }

        return $this->convertBasicInfoFromSource($customer);
    }

    /**
     * Sets the basic info from the WooCommerce customer.
     *
     * @param Customer $customer
     *
     * @return Customer
     */
    protected function convertBasicInfoFromSource(Customer $customer) : Customer
    {
        $customer->setFirstName($this->getFirstNameFromSource($customer->getUser()));
        $customer->setLastName($this->getLastNameFromSource($customer->getUser()));
        $customer->setEmail($this->getEmailAddressFromSource($customer->getUser()));

        return $customer;
    }

    /**
     * Gets the customer's first name from source.
     *
     * @param User|null $user
     * @return non-empty-string|null
     */
    protected function getFirstNameFromSource(?User $user) : ?string
    {
        if ($first_name = $this->source->get_first_name('edit')) {
            return $first_name;
        }

        if ($user && $user->getFirstName()) {
            return $user->getFirstName();
        }

        return null;
    }

    /**
     * Gets the customer's last name from source.
     *
     * @param User|null $user
     * @return non-empty-string|null
     */
    protected function getLastNameFromSource(?User $user) : ?string
    {
        if ($last_name = $this->source->get_last_name('edit')) {
            return $last_name;
        }

        if ($user && $user->getLastName()) {
            return $user->getLastName();
        }

        return null;
    }

    /**
     * Gets the customer's email address from source.
     *
     * @param User|null $user
     * @return non-empty-string|null
     */
    protected function getEmailAddressFromSource(?User $user) : ?string
    {
        if ($email = $this->source->get_email('edit')) {
            return $email;
        }

        if ($email = $this->source->get_billing_email('edit')) {
            return $email;
        }

        if ($user && $user->getEmail()) {
            return $user->getEmail();
        }

        return null;
    }

    /**
     * Converts a native payment method into a WooCommerce token.
     *
     * @since 0.1.0
     *
     * @param Customer $customer
     *
     * @return WC_Customer
     */
    public function convertToSource(Customer $customer) : WC_Customer
    {
        $this->source->set_id($customer->getId());
        $this->source->update_meta_data('mwc_remote_id', $customer->getRemoteId());

        $this->convertBasicInfoToSource($customer);

        if ($shippingAddress = $customer->getShippingAddress()) {
            $adaptedShippingAddress = (new AddressAdapter([]))->convertToSource($customer->getShippingAddress());
            $this->source->set_shipping_company($adaptedShippingAddress['company']);
            $this->source->set_shipping_first_name($adaptedShippingAddress['first_name']);
            $this->source->set_shipping_last_name($adaptedShippingAddress['last_name']);
            $this->source->set_shipping_address_1($adaptedShippingAddress['address_1']);
            $this->source->set_shipping_address_2($adaptedShippingAddress['address_2']);
            $this->source->set_shipping_city($adaptedShippingAddress['city']);
            $this->source->set_shipping_state($adaptedShippingAddress['state']);
            $this->source->set_shipping_postcode($adaptedShippingAddress['postcode']);
            $this->source->set_shipping_country($adaptedShippingAddress['country']);
        }

        if ($billingAddress = $customer->getBillingAddress()) {
            $adaptedBillingAddress = (new AddressAdapter([]))->convertToSource($customer->getBillingAddress());
            $this->source->set_billing_company($adaptedBillingAddress['company']);
            $this->source->set_billing_first_name($adaptedBillingAddress['first_name']);
            $this->source->set_billing_last_name($adaptedBillingAddress['last_name']);
            $this->source->set_billing_address_1($adaptedBillingAddress['address_1']);
            $this->source->set_billing_address_2($adaptedBillingAddress['address_2']);
            $this->source->set_billing_city($adaptedBillingAddress['city']);
            $this->source->set_billing_state($adaptedBillingAddress['state']);
            $this->source->set_billing_postcode($adaptedBillingAddress['postcode']);
            $this->source->set_billing_country($adaptedBillingAddress['country']);
        }

        return $this->source;
    }

    /**
     * Sets the basic info for the WooCommerce customer.
     *
     * @param Customer $customer
     */
    protected function convertBasicInfoToSource(Customer $customer) : void
    {
        $this->source->set_first_name($this->getFirstNameForSource($customer));
        $this->source->set_last_name($this->getLastNameForSource($customer));
        $this->source->set_email($this->getEmailForSource($customer));

        $this->convertUserInfoToSource($customer);
    }

    /**
     * Gets the first name for the WooCommerce customer.
     *
     * @param Customer $customer
     * @return string|null
     */
    protected function getFirstNameForSource(Customer $customer) : ?string
    {
        if ($firstName = $customer->getFirstName()) {
            return $firstName;
        }

        if ($user = $customer->getUser()) {
            return $user->getFirstName();
        }

        return null;
    }

    /**
     * Gets the last name for the WooCommerce customer.
     *
     * @param Customer $customer
     * @return string|null
     */
    protected function getLastNameForSource(Customer $customer) : ?string
    {
        if ($lastName = $customer->getLastName()) {
            return $lastName;
        }

        if ($user = $customer->getUser()) {
            return $user->getLastName();
        }

        return null;
    }

    /**
     * Gets the email address for the WooCommerce customer.
     *
     * @param Customer $customer
     * @return string|null
     */
    protected function getEmailForSource(Customer $customer) : ?string
    {
        if ($email = $customer->getEmail()) {
            return $email;
        }

        if ($user = $customer->getUser()) {
            return $user->getEmail();
        }

        return null;
    }

    /**
     * Sets the user info for the WooCommerce customer.
     *
     * @param Customer $customer
     */
    protected function convertUserInfoToSource(Customer $customer) : void
    {
        if ($user = $customer->getUser()) {
            $this->source->set_username($user->getHandle());
            $this->source->set_display_name($user->getDisplayName());
        }
    }
}

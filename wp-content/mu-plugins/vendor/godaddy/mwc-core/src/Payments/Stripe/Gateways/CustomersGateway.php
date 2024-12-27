<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways;

use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\CustomerAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use Stripe\Exception\ApiErrorException;

/**
 * Customers gateway.
 */
class CustomersGateway extends StripeGateway
{
    /**
     * Returns a Customer model based on a given id.
     *
     * @param string $id
     *
     * @return Customer
     * @throws ApiErrorException
     */
    public function get(string $id) : Customer
    {
        $this->maybeLogApiRequest(__METHOD__, ['id' => $id]);
        $stripeCustomer = $this->getClient()->customers->retrieve($id);
        $this->maybeLogApiResponse(__METHOD__, $stripeCustomer);

        return CustomerAdapter::getNewInstance()->convertToSource($stripeCustomer->toArray());
    }

    /**
     * Creates a customer in Stripe using the supplied Customer Model.
     *
     * @param Customer $customer
     *
     * @return Customer
     * @throws ApiErrorException
     */
    public function create(Customer $customer) : Customer
    {
        $args = ($adapter = CustomerAdapter::getNewInstance($customer))->convertFromSource();
        $this->maybeLogApiRequest(__METHOD__, $args, $customer);
        $stripeCustomer = $this->getClient()->customers->create($args);
        $this->maybeLogApiResponse(__METHOD__, $stripeCustomer);

        return $adapter->convertToSource($stripeCustomer->toArray());
    }

    /**
     * No-op: customer updating is not currently supported.
     *
     * @param Customer $customer
     *
     * @return Customer
     */
    public function update(Customer $customer) : Customer
    {
        return $customer;
    }
}

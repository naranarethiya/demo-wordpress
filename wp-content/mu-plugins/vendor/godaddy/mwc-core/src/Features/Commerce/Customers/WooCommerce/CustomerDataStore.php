<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\CreateOrUpdateCustomerOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCustomerRemoteIdException;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use WC_Customer;
use WC_Customer_Data_Store;

class CustomerDataStore extends WC_Customer_Data_Store
{
    protected CustomersServiceContract $customersService;

    public function __construct(CustomersServiceContract $customersService)
    {
        $this->customersService = $customersService;
    }

    /**
     * Updates a customer.
     *
     * @param mixed $customer
     * @return void
     */
    public function update(&$customer) : void
    {
        if ($customer instanceof WC_Customer) {
            $this->pushUpdatedCustomerToPlatform($customer);
        }

        parent::update($customer);
    }

    /**
     * Updates a customer in the Commerce platform.
     *
     * @param WC_Customer $wooCustomer
     * @return void
     */
    protected function pushUpdatedCustomerToPlatform(WC_Customer $wooCustomer) : void
    {
        $operation = CreateOrUpdateCustomerOperation::fromCustomer(
            CustomerAdapter::getNewInstance($wooCustomer)->convertFromSource()
        );

        try {
            try {
                $this->customersService->updateCustomer($operation);
            } catch (MissingCustomerRemoteIdException $exception) {
                $this->customersService->createOrUpdateCustomer($operation);
            }
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance('An error occurred trying to update a remote record for a registered customer: '.$exception->getMessage(), $exception);
        }
    }
}

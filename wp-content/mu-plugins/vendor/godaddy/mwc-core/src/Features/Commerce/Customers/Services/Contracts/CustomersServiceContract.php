<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\Contracts\CreateOrUpdateCustomerOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Responses\Contracts\CreateOrUpdateCustomerResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

interface CustomersServiceContract
{
    /**
     * Creates or updates the customer.
     *
     * Intended to use a CustomersProviderContract instance to create or update a customer entity in a remote Customers
     * service
     *
     * @param CreateOrUpdateCustomerOperationContract $operation
     * @return CreateOrUpdateCustomerResponseContract
     * @throws CommerceExceptionContract
     */
    public function createOrUpdateCustomer(CreateOrUpdateCustomerOperationContract $operation) : CreateOrUpdateCustomerResponseContract;

    /**
     * Updates a customer in the Commerce platform.
     *
     * @param CreateOrUpdateCustomerOperationContract $operation
     * @return CreateOrUpdateCustomerResponseContract
     * @throws CommerceExceptionContract
     */
    public function updateCustomer(CreateOrUpdateCustomerOperationContract $operation) : CreateOrUpdateCustomerResponseContract;
}

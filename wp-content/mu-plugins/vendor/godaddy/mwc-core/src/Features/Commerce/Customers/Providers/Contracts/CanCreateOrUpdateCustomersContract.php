<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpsertCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

interface CanCreateOrUpdateCustomersContract
{
    /**
     * Creates or updates a customer.
     *
     * @param UpsertCustomerInput $input
     * @return CustomerBase
     * @throws CommerceExceptionContract
     */
    public function createOrUpdate(UpsertCustomerInput $input) : CustomerBase;
}

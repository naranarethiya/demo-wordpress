<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpdateCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

interface CanUpdateCustomersContract
{
    /**
     * Updates a customer.
     *
     * @param UpdateCustomerInput $input
     * @return CustomerOutput
     * @throws CommerceExceptionContract
     */
    public function update(UpdateCustomerInput $input) : CustomerOutput;
}

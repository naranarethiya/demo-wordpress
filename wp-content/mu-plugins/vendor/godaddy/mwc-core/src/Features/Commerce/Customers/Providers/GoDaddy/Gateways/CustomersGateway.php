<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts\CustomersGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpdateCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpsertCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Adapters\UpdateCustomerRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Adapters\UpsertCustomerRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

class CustomersGateway extends AbstractGateway implements CustomersGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * Creates or updates a customer.
     *
     * @param UpsertCustomerInput $input
     * @return CustomerBase
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function createOrUpdate(UpsertCustomerInput $input) : CustomerBase
    {
        /** @var CustomerBase $result */
        $result = $this->doAdaptedRequest(UpsertCustomerRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a customer.
     *
     * @param UpdateCustomerInput $input
     * @return CustomerOutput
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function update(UpdateCustomerInput $input) : CustomerOutput
    {
        /** @var CustomerOutput $result */
        $result = $this->doAdaptedRequest(UpdateCustomerRequestAdapter::getNewInstance($input));

        return $result;
    }
}

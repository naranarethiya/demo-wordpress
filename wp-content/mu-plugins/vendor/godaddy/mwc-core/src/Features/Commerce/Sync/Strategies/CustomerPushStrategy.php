<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Strategies;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\CreateOrUpdateCustomerOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Strategies\Contracts\CustomerPushStrategyContract;

class CustomerPushStrategy implements CustomerPushStrategyContract
{
    /** @var CustomerContract */
    protected CustomerContract $customer;

    /** @var CustomersServiceContract */
    protected CustomersServiceContract $customersService;

    /**
     * Constructor.
     *
     * @param CustomerContract $customer
     * @param CustomersServiceContract $customersService
     */
    public function __construct(CustomerContract $customer, CustomersServiceContract $customersService)
    {
        $this->customer = $customer;
        $this->customersService = $customersService;
    }

    /**
     * {@inheritDoc}
     */
    public function sync() : void
    {
        $this->customersService->createOrUpdateCustomer(
            CreateOrUpdateCustomerOperation::fromCustomer($this->customer)
        );
    }
}

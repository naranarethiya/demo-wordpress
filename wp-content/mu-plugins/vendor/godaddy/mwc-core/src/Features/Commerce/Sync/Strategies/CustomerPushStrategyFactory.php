<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Strategies;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\CustomersService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Strategies\Contracts\CustomerPushStrategyContract;

class CustomerPushStrategyFactory
{
    use CanGetNewInstanceTrait;

    /**
     * Gets instance of push strategy class meant for the given customer.
     *
     * @param CustomerContract $customer
     *
     * @return CustomerPushStrategyContract
     * @throws CommerceExceptionContract
     */
    public function getStrategyFor(CustomerContract $customer) : CustomerPushStrategyContract
    {
        return new CustomerPushStrategy($customer, CustomersService::getNewInstance());
    }
}

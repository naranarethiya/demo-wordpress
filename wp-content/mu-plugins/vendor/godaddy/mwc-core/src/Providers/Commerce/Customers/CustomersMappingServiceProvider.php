<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Customers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\CustomersMappingService;

class CustomersMappingServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CustomersMappingServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(CustomersMappingServiceContract::class, CustomersMappingService::class);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Customers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\CustomersService;

class CustomersServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CustomersServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(CustomersServiceContract::class, CustomersService::class);
    }
}

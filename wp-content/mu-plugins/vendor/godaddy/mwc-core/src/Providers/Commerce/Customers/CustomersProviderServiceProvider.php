<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Customers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts\CustomersProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\CustomersProvider;

class CustomersProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CustomersProviderContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(CustomersProviderContract::class, CustomersProvider::class);
    }
}

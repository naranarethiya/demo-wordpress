<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts\OrdersProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\OrdersProvider;

class OrdersProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [OrdersProviderContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(OrdersProviderContract::class, OrdersProvider::class);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\OrdersService;

class OrdersServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [OrdersServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(OrdersServiceContract::class, OrdersService::class);
    }
}

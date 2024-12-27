<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\OrdersCachingService;

class OrdersCachingServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [OrdersCachingServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(OrdersCachingServiceContract::class, OrdersCachingService::class);
    }
}

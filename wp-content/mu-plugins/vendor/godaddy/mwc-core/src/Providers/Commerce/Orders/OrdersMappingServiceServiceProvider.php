<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\OrdersMappingService;

class OrdersMappingServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [OrdersMappingServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(OrdersMappingServiceContract::class, OrdersMappingService::class);
    }
}

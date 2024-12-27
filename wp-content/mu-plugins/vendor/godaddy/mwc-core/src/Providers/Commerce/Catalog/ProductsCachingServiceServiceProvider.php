<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductsCachingService;

/**
 * Service provider for the Products Caching Service.
 */
class ProductsCachingServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ProductsCachingServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(ProductsCachingServiceContract::class, ProductsCachingService::class);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductsService;

class ProductsServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ProductsServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(ProductsServiceContract::class, ProductsService::class);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ListProductsService;

/**
 * Service provider for the List Products Service.
 */
class ListProductsServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ListProductsServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(ListProductsServiceContract::class, ListProductsService::class);
    }
}

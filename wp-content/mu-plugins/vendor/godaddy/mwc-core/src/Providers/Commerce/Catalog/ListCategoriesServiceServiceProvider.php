<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListCategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ListCategoriesService;

/**
 * Service provider for the List Categories Service.
 */
class ListCategoriesServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ListCategoriesServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(ListCategoriesServiceContract::class, ListCategoriesService::class);
    }
}

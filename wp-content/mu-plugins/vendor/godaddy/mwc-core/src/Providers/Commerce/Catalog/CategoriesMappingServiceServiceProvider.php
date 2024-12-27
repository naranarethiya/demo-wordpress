<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\CategoriesMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingServiceContract;

/**
 * Service provider for the Categories Mapping Service.
 */
class CategoriesMappingServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CategoriesMappingServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CategoriesMappingServiceContract::class, CategoriesMappingService::class);
    }
}

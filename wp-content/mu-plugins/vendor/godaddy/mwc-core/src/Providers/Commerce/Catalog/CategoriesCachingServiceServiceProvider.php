<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\CategoriesCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesCachingServiceContract;

class CategoriesCachingServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CategoriesCachingServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CategoriesCachingServiceContract::class, CategoriesCachingService::class);
    }
}

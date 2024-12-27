<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\Contracts\ListProductsCachingHelperContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\ListProductsCachingHelper;

/**
 * Service provider for the List Products Caching Service.
 */
class ListProductsCachingHelperServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ListProductsCachingHelperContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(ListProductsCachingHelperContract::class, ListProductsCachingHelper::class);
    }
}

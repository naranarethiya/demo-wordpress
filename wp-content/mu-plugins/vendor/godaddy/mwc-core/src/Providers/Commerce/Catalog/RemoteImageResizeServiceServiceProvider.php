<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\RemoteImageResizeServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\IsteamRemoteImageResizeService;

class RemoteImageResizeServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [RemoteImageResizeServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(RemoteImageResizeServiceContract::class, IsteamRemoteImageResizeService::class);
    }
}

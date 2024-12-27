<?php

namespace GoDaddy\WordPress\MWC\Core\Providers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Common\Stores\Contracts\StoreRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Stores\Repositories\StoreRepository;

class StoreRepositoryServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [StoreRepositoryContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(StoreRepositoryContract::class, StoreRepository::class);
    }
}

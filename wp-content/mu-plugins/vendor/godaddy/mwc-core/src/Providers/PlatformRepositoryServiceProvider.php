<?php

namespace GoDaddy\WordPress\MWC\Core\Providers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Repositories\ManagedWordPressPlatformRepository;

class PlatformRepositoryServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [PlatformRepositoryContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(PlatformRepositoryContract::class, ManagedWordPressPlatformRepository::class);
    }
}

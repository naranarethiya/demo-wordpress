<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\PercentageJitterProviderContract;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\PercentageJitterProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyFactoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Factories\CachingStrategyFactory;

/**
 * Service provider for the Caching Strategy Service.
 */
class CachingStrategyServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        CachingStrategyFactoryContract::class,
        PercentageJitterProviderContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CachingStrategyFactoryContract::class, CachingStrategyFactory::class);
        $this->getContainer()->bind(PercentageJitterProviderContract::class, PercentageJitterProvider::class);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\PersistentCachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\TransientCachingStrategy;

/**
 * Service provider for the persistent caching strategy.
 */
class PersistentCachingStrategyServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [PersistentCachingStrategyContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(PersistentCachingStrategyContract::class, TransientCachingStrategy::class);
    }
}

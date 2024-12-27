<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Factories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyFactoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\PersistentCachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\WpCacheCachingStrategy;

/**
 * Caching strategy factory that always uses {@see WpCacheCachingStrategy}.
 */
class WpCacheCachingStrategyFactory implements CachingStrategyFactoryContract
{
    /** @var WpCacheCachingStrategy the WP caching strategy injected instance */
    protected WpCacheCachingStrategy $wpCacheCachingStrategy;

    public function __construct(WpCacheCachingStrategy $persistentCachingStrategy)
    {
        $this->wpCacheCachingStrategy = $persistentCachingStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function makeCachingStrategy() : PersistentCachingStrategyContract
    {
        return $this->wpCacheCachingStrategy;
    }
}

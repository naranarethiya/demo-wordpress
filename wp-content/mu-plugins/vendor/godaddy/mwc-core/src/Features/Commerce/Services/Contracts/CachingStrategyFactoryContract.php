<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\CachingStrategyContract;

/**
 * Provides the default method signatures to a caching strategy factory.
 */
interface CachingStrategyFactoryContract
{
    /**
     * Gets a {@see CachingStrategyContract} concrete implementation instance.
     *
     * @return CachingStrategyContract
     */
    public function makeCachingStrategy() : CachingStrategyContract;
}

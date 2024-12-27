<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\CachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\PersistentCachingStrategyContract;

/**
 * The caching strategy based on a memory with persistence approach.
 */
class MemoryWithPersistenceCachingStrategy implements CachingStrategyContract
{
    use CanGetNewInstanceTrait;

    /** @var MemoryCachingStrategy the memory caching strategy injected instance */
    protected MemoryCachingStrategy $memoryCachingStrategy;

    /** @var PersistentCachingStrategyContract the persistent caching strategy injected instance */
    protected PersistentCachingStrategyContract $persistentCachingStrategy;

    /**
     * The MemoryWithPersistenceCachingStrategy constructor.
     */
    public function __construct(MemoryCachingStrategy $memoryCachingStrategy, PersistentCachingStrategyContract $persistentCachingStrategy)
    {
        $this->memoryCachingStrategy = $memoryCachingStrategy;
        $this->persistentCachingStrategy = $persistentCachingStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, string $group)
    {
        return $this->memoryCachingStrategy->get($key, $group);
    }

    /**
     * {@inheritDoc}
     */
    public function getMany(array $keys, string $group) : array
    {
        return $this->memoryCachingStrategy->getMany($keys, $group);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key, string $group) : void
    {
        $this->memoryCachingStrategy->remove($key, $group);
        $this->persistentCachingStrategy->remove($key, $group);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, string $group, string $jsonResource, int $ttl) : void
    {
        $this->memoryCachingStrategy->set($key, $group, $jsonResource, $ttl);
        $this->persistentCachingStrategy->set($key, $group, $jsonResource, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function setMany(string $group, array $jsonResources, int $ttl) : void
    {
        $this->memoryCachingStrategy->setMany($group, $jsonResources, $ttl);
        $this->persistentCachingStrategy->setMany($group, $jsonResources, $ttl);
    }
}

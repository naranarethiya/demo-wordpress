<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Contract for caching strategies.
 */
interface CachingStrategyContract
{
    /**
     * Gets a resource from the cache.
     *
     * @param string $key
     * @param string $group
     * @return mixed|null
     */
    public function get(string $key, string $group);

    /**
     * Gets multiple resources from the cache.
     *
     * @param string[] $keys
     * @param string $group
     * @return array<mixed>
     */
    public function getMany(array $keys, string $group) : array;

    /**
     * Adds a JSON resource to the cache.
     *
     * @param string $key
     * @param string $group
     * @param string $jsonResource
     * @param int $ttl
     * @throws CachingStrategyException
     */
    public function set(string $key, string $group, string $jsonResource, int $ttl) : void;

    /**
     * Adds multiple JSON resources to the cache.
     *
     * @param string $group
     * @param array<string, string> $jsonResources array key is the cache key, value is the JSON resource
     * @param int $ttl
     * @return void
     * @throws CachingStrategyException
     */
    public function setMany(string $group, array $jsonResources, int $ttl) : void;

    /**
     * Removes a resource from the cache.
     *
     * @param string $key
     * @param string $group
     * @throws CachingStrategyException
     */
    public function remove(string $key, string $group) : void;
}

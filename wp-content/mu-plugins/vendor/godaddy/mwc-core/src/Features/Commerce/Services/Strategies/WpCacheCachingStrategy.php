<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\PersistentCachingStrategyContract;

/**
 * Caching strategy that relies on the WordPress cache.
 */
class WpCacheCachingStrategy implements PersistentCachingStrategyContract
{
    /**
     * Gets a resource from the WordPress cache.
     *
     * @see wp_cache_get()
     *
     * @param string $key
     * @param string $group
     * @return mixed|null
     */
    public function get(string $key, string $group)
    {
        $result = wp_cache_get($key, $group);

        // false would signal that the resource was not found, but in this case we want to return null instead
        return false === $result ? null : $result;
    }

    /**
     * Gets multiple resources from the WordPress cache.
     *
     * @see wp_cache_get_multiple()
     *
     * @param string[] $keys
     * @param string $group
     * @return array<string, mixed|null>
     */
    public function getMany(array $keys, string $group) : array
    {
        $values = [];

        foreach (wp_cache_get_multiple($keys, $group) as $key => $value) {
            // false would signal that the resource was not found, but in this case we want to return null instead
            $values[$key] = false === $value ? null : $value;
        }

        return $values;
    }

    /**
     * Adds a JSON resource to the WordPress cache.
     *
     * @see wp_cache_set()
     *
     * @param string $key
     * @param string $group
     * @param string $jsonResource
     * @param int $ttl
     * @throws CachingStrategyException
     */
    public function set(string $key, string $group, string $jsonResource, int $ttl) : void
    {
        if (! wp_cache_set($key, $jsonResource, $group, $ttl)) {
            throw new CachingStrategyException(sprintf('Failed to set cache key "%s" in group "%s"', $key, $group));
        }
    }

    /**
     * Adds multiple JSON resources to the WordPress cache.
     *
     * @see wp_cache_set_multiple()
     *
     * @param string $group
     * @param array<string, string> $jsonResources array key is the cache key, value is the JSON resource
     * @param int $ttl
     * @return void
     * @throws CachingStrategyException
     */
    public function setMany(string $group, array $jsonResources, int $ttl) : void
    {
        foreach (wp_cache_set_multiple($jsonResources, $group, $ttl) as $key => $result) {
            if ($result === false) {
                throw new CachingStrategyException(sprintf('Failed to set cache key "%s" in group "%s"', $key, $group));
            }
        }
    }

    /**
     * Removes a resource from the WordPress cache.
     *
     * @see wp_cache_delete()
     *
     * @param string $key
     * @param string $group
     */
    public function remove(string $key, string $group) : void
    {
        /*
         * We do not throw an exception on failure here because it can return `false` just if the item never existed
         * in the cache to begin with, which isn't a "failure" we want to be concerned about. Throwing exceptions here
         * just results in excessive noise.
         */
        wp_cache_delete($key, $group);
    }
}

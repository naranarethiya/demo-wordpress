<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\CachingStrategyContract;

/**
 * Caching strategy that stores resources in memory for the current thread.
 */
class MemoryCachingStrategy implements CachingStrategyContract
{
    /** @var array<string, array<string, string>> */
    protected static array $groups = [];

    /**
     * Gets a resource from memory.
     *
     * @param string $key
     * @param string $group
     *
     * @return string|null
     */
    public function get(string $key, string $group) : ?string
    {
        return TypeHelper::string(ArrayHelper::get(static::$groups, "{$group}.{$key}"), '') ?: null;
    }

    /**
     * Gets multiple resources from memory.
     *
     * @param string[] $keys
     * @param string $group
     *
     * @return array<string, string>
     */
    public function getMany(array $keys, string $group) : array
    {
        $resources = TypeHelper::array(ArrayHelper::get(static::$groups, $group, []), []);

        return TypeHelper::arrayOfStrings(array_intersect_key($resources, array_flip($keys)), false);
    }

    /**
     * Adds a JSON resource to memory.
     *
     * @param string $key
     * @param string $group
     * @param string $jsonResource
     * @param int $ttl
     */
    public function set(string $key, string $group, string $jsonResource, int $ttl = 0) : void
    {
        ArrayHelper::set(static::$groups, "{$group}.{$key}", $jsonResource);
    }

    /**
     * Adds multiple JSON resources to memory.
     *
     * @param string $group
     * @param array<string, string> $jsonResources array key is the cache key, value is the JSON resource
     * @param int $ttl
     */
    public function setMany(string $group, array $jsonResources, int $ttl = 0) : void
    {
        foreach ($jsonResources as $key => $jsonResource) {
            $this->set($key, $group, $jsonResource);
        }
    }

    /**
     * Removes a resource from memory.
     *
     * @param string $key
     * @param string $group
     */
    public function remove(string $key, string $group) : void
    {
        ArrayHelper::remove(static::$groups, "{$group}.{$key}");
    }
}

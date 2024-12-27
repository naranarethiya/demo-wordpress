<?php

namespace GoDaddy\WordPress\MWC\Common\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\Types\CacheConfigurations;
use GoDaddy\WordPress\MWC\Common\Cache\Types\CacheExtensions;
use GoDaddy\WordPress\MWC\Common\Cache\Types\CacheHttpResponse;
use GoDaddy\WordPress\MWC\Common\Cache\Types\CacheVersions;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * Main cache handler.
 *
 * This caches data in memory in a static property {@see Cache::$cache}. Data may also optionally be saved to
 * persistent database cache using transients {@see Cache::setPersisted()}.
 */
class Cache extends AbstractCache
{
    /**
     * The current static cache instance.
     *
     * @NOTE: This is always checked first before checking for the persistent database cache.
     *
     * @var array<mixed>
     */
    protected static $cache = [];

    /** @var string the cache key prefix applied to subclass keys */
    protected $keyPrefix = 'gd_';

    /**
     * Creates an instance for caching configurations.
     *
     * @return CacheConfigurations
     */
    public static function configurations() : CacheConfigurations
    {
        return new CacheConfigurations();
    }

    /**
     * Creates an instance for caching extensions.
     *
     * @return CacheExtensions
     */
    public static function extensions() : CacheExtensions
    {
        return new CacheExtensions();
    }

    /**
     * Creates and instance for caching HTTP responses.
     *
     * @return CacheHttpResponse
     */
    public static function httpResponse() : CacheHttpResponse
    {
        return new CacheHttpResponse();
    }

    /**
     * Creates an instance for caching extension versions.
     *
     * @return CacheVersions
     */
    public static function versions() : CacheVersions
    {
        return new CacheVersions();
    }

    /**
     * Clears the current cache.
     *
     * @NOTE: The persisted stores may rely on configurations so be sure to clear them first before their dependencies
     *
     * @param bool $persisted
     * @return void
     */
    public function clear(bool $persisted = true)
    {
        if ($persisted) {
            $this->clearPersisted();
        }

        ArrayHelper::remove(self::$cache, $this->getKey());
    }

    /**
     * Clears the persisted store.
     *
     * @return void
     */
    protected function clearPersisted()
    {
        if (WordPressRepository::hasWordPressInstance()) {
            delete_transient($this->getKey());
        }
    }

    /**
     * Fetch from cache.
     *
     * If the cache has no value, it attempts to get and set the value by invoking the given $loader.
     *
     * @param string $key cache key
     * @param callable $loader function to call
     *
     * @deprecated use {@see remember()}
     *
     * @return mixed
     */
    public function fetch(string $key, callable $loader)
    {
        $cache = $this->get([]);

        if (! empty($currentValue = ArrayHelper::get($cache, $key))) {
            return $currentValue;
        }

        $value = $loader();
        ArrayHelper::set($cache, $key, $value);

        $this->set($cache);

        return $value;
    }

    /**
     * Gets a cached value from the static store.
     *
     * @param mixed $default
     * @return mixed|null
     */
    public function get($default = null)
    {
        if (ArrayHelper::has(self::$cache, $this->getKey())) {
            return ArrayHelper::get(self::$cache, $this->getKey(), $default);
        }

        $persisted = $this->getPersisted();

        if ($persisted !== false) {
            return $persisted;
        }

        return $default;
    }

    /**
     * Gets a cached value from the persisted store.
     *
     * @return mixed|null
     */
    public function getPersisted()
    {
        $value = get_transient($this->getKey());
        if ($value !== false) {
            $this->set($value, false);
        }

        return $value;
    }

    /**
     * Get the full key string.
     *
     * @return string
     */
    public function getKey() : string
    {
        return "{$this->keyPrefix}{$this->key}";
    }

    /**
     * Sets a value in the cache.
     *
     * @param mixed $value
     * @param bool $persisted
     * @return void
     */
    public function set($value, bool $persisted = true)
    {
        // NOTE: Avoid hitting persisted source if value has not changed {JO: 2021-09-01}
        if (! $this->shouldSet($value)) {
            return;
        }

        ArrayHelper::set(self::$cache, $this->getKey(), $value);

        if ($persisted) {
            $this->setPersisted($value);
        }
    }

    /**
     * Sets a value in the persisted store.
     *
     * @param mixed $value
     * @return void
     */
    protected function setPersisted($value)
    {
        if (WordPressRepository::hasWordPressInstance()) {
            set_transient($this->getKey(), $value, $this->expires);
        }
    }

    /**
     * Checks if a valid change has occurred that cache should be set.
     *
     * @param mixed $valueToBeSet
     *
     * @return bool
     */
    public function shouldSet($valueToBeSet) : bool
    {
        // @NOTE: Avoid edge case where current cache key is void {JO: 2021-09-01}
        if (! ArrayHelper::has(self::$cache, $this->getKey())) {
            return true;
        }

        $currentCache = $this->get();

        if (is_object($valueToBeSet)) {
            return $currentCache != $valueToBeSet;
        }

        // @NOTE: Strict comparison for non-objects required so things like false and null don't equate {JO: 2021-09-01}
        return $currentCache !== $valueToBeSet;
    }
}

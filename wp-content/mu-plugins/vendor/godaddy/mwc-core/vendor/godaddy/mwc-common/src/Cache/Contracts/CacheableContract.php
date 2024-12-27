<?php

namespace GoDaddy\WordPress\MWC\Common\Cache\Contracts;

/**
 * Cacheable contract.
 */
interface CacheableContract
{
    /**
     * Clears the current cache.
     *
     * @param bool $persisted
     */
    public function clear(bool $persisted = true);

    /**
     * Sets when the cache should expire.
     *
     * @param int $seconds
     * @return $this
     */
    public function expires(int $seconds);

    /**
     * Fetch from cache.
     *
     * @param string $key cache key
     * @param callable $loader function to call
     */
    public function fetch(string $key, callable $loader);

    /**
     * Gets a cached value from the static store.
     *
     * @param mixed $default
     * @return mixed
     */
    public function get($default = null);

    /**
     * Sets what key the data will be stored in within the cache.
     *
     * @param string $key
     * @return $this
     */
    public function key(string $key);

    /**
     * Sets a value in the cache.
     *
     * @param mixed $value
     * @param bool $persisted
     */
    public function set($value, bool $persisted = true);

    /**
     * Sets the type of data being cached.
     *
     * @param string $type
     * @return $this
     */
    public function type(string $type);
}

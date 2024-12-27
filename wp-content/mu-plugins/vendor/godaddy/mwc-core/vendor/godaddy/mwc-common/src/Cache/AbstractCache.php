<?php

namespace GoDaddy\WordPress\MWC\Common\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;

/**
 * Abstract Cache Class.
 *
 * This abstract implements some common methods from the {@see CacheableContract}.
 */
abstract class AbstractCache implements CacheableContract
{
    /**
     * How long in seconds should the cache be kept for.
     *
     * In-memory caches are reset on each page change and will not have an expiry set.
     * Persistent caches will respect the expiry.
     *
     * @var int
     */
    protected $expires;

    /** @var string the cache key */
    protected $key = 'system';

    /** @var string the type of object we are caching */
    protected $type;

    /**
     * Sets when the cache should expire.
     *
     * @param int $seconds
     * @return $this
     */
    public function expires(int $seconds)
    {
        $this->expires = $seconds;

        return $this;
    }

    /**
     * Get an item from the cache, or execute the given callable and store the result.
     *
     * @param callable $loader function that returns value to cache.
     *
     * @return mixed
     */
    public function remember(callable $loader)
    {
        $value = $this->get(null);

        if (null === $value) {
            $this->set($value = $loader());
        }

        return $value;
    }

    /**
     * Sets what key the data will be stored in within the cache.
     *
     * @param string $key
     * @return $this
     */
    public function key(string $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the full key string.
     *
     * @return string
     */
    public function getKey() : string
    {
        return $this->key;
    }

    /**
     * Sets the type of data being cached.
     *
     * @param string $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = $type;

        return $this;
    }
}

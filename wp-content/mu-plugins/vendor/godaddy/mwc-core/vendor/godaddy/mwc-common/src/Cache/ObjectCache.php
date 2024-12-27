<?php

namespace GoDaddy\WordPress\MWC\Common\Cache;

/**
 * Object cache handler.
 *
 * This caches data using WP Object Cache {@link https://developer.wordpress.org/reference/classes/wp_object_cache/}
 * For platforms that DO NOT use object caching, items are cached in-memory only, for the duration of a single request.
 * For platforms that DO use object caching, items are persisted according to the supplied {@see ObjectCache::$expires} value.
 */
class ObjectCache extends AbstractCache
{
    /** @var string the prefix applied to subclass types (cache groups) */
    protected string $typePrefix = 'gd_';

    protected $expires = 0;

    /**
     * {@inheritDoc}
     */
    public function clear(bool $persisted = true) : void
    {
        wp_cache_delete($this->key, $this->getCacheGroup());
    }

    /**
     * This is not implemented; use {@see static::remember()} instead.
     *
     * {@inheritDoc}
     */
    public function fetch(string $key, callable $loader) : void
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function get($default = null)
    {
        $found = null; // passed by reference -- this is whether the key was found in the cache or not

        $value = wp_cache_get($this->key, $this->getCacheGroup(), false, $found);

        if (false === $found) {
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function set($value, bool $persisted = true) : void
    {
        wp_cache_set($this->key, $value, $this->getCacheGroup(), $this->expires);
    }

    /**
     * Gets the cache group.
     *
     * @return string
     */
    protected function getCacheGroup() : string
    {
        return "{$this->typePrefix}{$this->type}";
    }
}

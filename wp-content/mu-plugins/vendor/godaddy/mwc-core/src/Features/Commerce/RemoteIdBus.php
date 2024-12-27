<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class RemoteIdBus
{
    use CanGetNewInstanceTrait;

    /**
     * @var string A prefix for the cache key.
     */
    protected const KEY_PREFIX = 'commerceRemoteId_';

    /**
     * @var CacheableContract The object storage
     */
    protected CacheableContract $cache;

    final public function __construct(CacheableContract $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Static constructor that uses the specified key for storing the remote ID into the object storage.
     *
     * @param string $key The key to use when setting/getting this remote ID.
     * @return static
     */
    public static function withKey(string $key)
    {
        return new static((new Cache())->key(static::KEY_PREFIX.$key));
    }

    /**
     * Sets the Remote ID to object storage.
     *
     * @param string $remoteId
     * @return $this
     */
    public function set(string $remoteId)
    {
        $this->cache->set($remoteId, false);

        return $this;
    }

    /**
     * Gets the Remote ID from the object storage.
     *
     * @return non-empty-string|null
     */
    public function get() : ?string
    {
        return TypeHelper::string($this->cache->get(), '') ?: null;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Connected channels cache handler class.
 */
class ConnectedChannelsCache extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 2 * HOUR_IN_SECONDS;

    /** @var string the cache key */
    protected $key = 'gdm_connected_channels';

    /**
     * Constructor.
     */
    final public function __construct()
    {
        $this->type($this->key);
    }
}

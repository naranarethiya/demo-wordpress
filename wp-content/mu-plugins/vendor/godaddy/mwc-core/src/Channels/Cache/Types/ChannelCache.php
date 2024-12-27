<?php

namespace GoDaddy\WordPress\MWC\Core\Channels\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Channel cache handler class.
 *
 * @method static static getNewInstance(string $channelId)
 */
class ChannelCache extends Cache implements CacheableContract
{
    use CanGetNewInstanceTrait;

    /** @var int how long in seconds the cache should be kept for */
    protected $expires = DAY_IN_SECONDS;

    /**
     * Constructor.
     *
     * @param string $channelId Unique ID of the channel.
     */
    final public function __construct(string $channelId)
    {
        $this->type('channel');
        $this->key(sprintf('channel_%s', strtolower($channelId)));
    }
}

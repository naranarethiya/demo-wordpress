<?php

namespace GoDaddy\WordPress\MWC\Common\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;

/**
 * Extensions cache.
 *
 * @since 1.0.0
 */
final class CacheVersions extends Cache implements CacheableContract
{
    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 5400;

    /** @var string the cache key. {llessa 2022-08-04} number suffix added to bust previous cache in sites. */
    protected $key = 'versions_2';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->type('versions');
    }
}

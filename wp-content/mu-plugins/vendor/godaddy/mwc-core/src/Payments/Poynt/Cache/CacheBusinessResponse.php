<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Business;

/**
 * Cache for errors received trying to authenticate against the MWC API.
 */
class CacheBusinessResponse extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    /** @var string the type of object we are caching */
    protected $type = 'business';

    /** @var string the cache key prefix applied to subclass keys */
    protected $keyPrefix = 'mwc_payments_poynt_';

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 900;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->key($this->type);
    }

    /**
     * Gets a cached value from the static store with instance of check.
     *
     * @param $default
     * @return Business|null
     */
    public function get($default = null) : ?Business
    {
        $business = parent::get($default);

        return ($business instanceof Business) ? $business : null;
    }
}

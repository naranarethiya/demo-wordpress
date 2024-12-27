<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

class CacheLastTimeCheckedTimestamp extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 600;

    /** @var string the cache key */
    protected $key = 'mwc_feature_flags_last_time_checked_timestamp';

    /** @var string the type of object we are caching */
    protected $type = 'mwc_feature_flags_last_time_checked_timestamp';
}

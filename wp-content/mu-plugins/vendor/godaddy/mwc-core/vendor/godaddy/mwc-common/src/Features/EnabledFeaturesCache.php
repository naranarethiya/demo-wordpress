<?php

namespace GoDaddy\WordPress\MWC\Common\Features;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class EnabledFeaturesCache extends Cache
{
    use CanGetNewInstanceTrait;

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 5;

    /** @var string the cache key */
    protected $key = 'mwc_enabled_feature_flags';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->type('enabled_features');
    }
}

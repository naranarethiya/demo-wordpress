<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Cache used to prevent multiple merchant provisioning requests.
 */
class HasRecentMerchantProvisioningAttemptCache extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = HOUR_IN_SECONDS;

    /** @var string the cache key */
    protected $key = 'gdm_has_recent_provision_merchant_attempt';

    /**
     * Constructor.
     */
    final public function __construct()
    {
        $this->type($this->key);
    }

    /**
     * Determines whether there is a recent merchant provisioning attempt.
     *
     * @return bool
     */
    public function hasRecentAttempt() : bool
    {
        return (bool) $this->get(false);
    }
}

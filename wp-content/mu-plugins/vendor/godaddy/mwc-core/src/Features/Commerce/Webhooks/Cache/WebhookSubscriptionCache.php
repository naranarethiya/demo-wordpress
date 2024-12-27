<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Provides a cache for webhook subscriptions.
 *
 * Webhook Subscriptions are retrieved via the commerce context ID, so this cache is keyed by that ID.
 *
 * @method static static getInstance(string $commerceContextId)
 */
class WebhookSubscriptionCache extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    protected $expires = DAY_IN_SECONDS;

    final public function __construct(string $commerceContextId)
    {
        $this->type('commerce');
        $this->key("commerce_webhook_subscription_{$commerceContextId}");
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\ObjectCache;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CreateCommerceContextsTableAction;

/**
 * Commerce Context cache handler class.
 * This caches the auto-incrementing ID of a context record, keyed by the store ID.
 * {@see CreateCommerceContextsTableAction}.
 *
 * This record is cached using object caching, which means if object caching is not enabled for a site then it's
 * cached in memory only, for the duration of a single request.
 *
 * @method static static getInstance(string $storeId)
 */
class CommerceContextCache extends ObjectCache
{
    use IsSingletonTrait;

    protected $expires = DAY_IN_SECONDS;

    final public function __construct(string $storeId)
    {
        $this->type('commerce');
        $this->key("commerce_context_{$storeId}");
    }
}

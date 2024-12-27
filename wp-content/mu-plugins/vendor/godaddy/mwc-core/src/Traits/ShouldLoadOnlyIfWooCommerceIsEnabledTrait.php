<?php

namespace GoDaddy\WordPress\MWC\Core\Traits;

use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

trait ShouldLoadOnlyIfWooCommerceIsEnabledTrait
{
    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return WooCommerceRepository::isWooCommerceActive();
    }
}

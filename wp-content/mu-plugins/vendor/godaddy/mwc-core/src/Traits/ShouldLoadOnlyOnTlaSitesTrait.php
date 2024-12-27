<?php

namespace GoDaddy\WordPress\MWC\Core\Traits;

use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;

trait ShouldLoadOnlyOnTlaSitesTrait
{
    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return static::isTlaSite();
    }

    /**
     * Determines if the site is originating from an internal TLA account.
     */
    protected static function isTlaSite() : bool
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isTlaSite();
        } catch (PlatformRepositoryException $e) {
            return false;
        }
    }
}

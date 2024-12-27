<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Traits;

use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * A trait that provides a helper method to determine whether a Managed WooCommerce feature should be loaded.
 *
 * TODO: move this trait to mwc-common {wvega 2022-01-05} - https://jira.godaddy.com/browse/MWC-3870
 */
trait IsManagedWooCommerceFeatureTrait
{
    /**
     * Determines whether a feature created for Managed WooCommerce users should load.
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    protected static function shouldLoadManagedWooCommerceFeature() : bool
    {
        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan()
            && WooCommerceRepository::isWooCommerceActive()
            && ManagedWooCommerceRepository::isAllowedToUseNativeFeatures();
    }
}

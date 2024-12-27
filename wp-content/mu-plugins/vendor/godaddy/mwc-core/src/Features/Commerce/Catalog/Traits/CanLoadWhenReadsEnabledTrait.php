<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;

/**
 * Trait for loading components when the catalog reads are enabled.
 */
trait CanLoadWhenReadsEnabledTrait
{
    /**
     * Should load the integration only when catalog reads are enabled.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }
}

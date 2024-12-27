<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;

trait CanDetermineShouldReadProductsTrait
{
    /**
     * Determines whether we should read related products.
     *
     * @return bool
     */
    protected function shouldReadProducts() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }
}

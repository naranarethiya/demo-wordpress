<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\Traits;

/**
 * Provides a standardized way to convert product IDs as a parameter to queries.
 */
trait CanGetProductIdsAsStringTrait
{
    /**
     * Gets the product IDs as a query arg string.
     *
     * @param array<string|int> $productIds
     * @return string
     */
    protected function getProductIdsAsString(array $productIds) : string
    {
        return implode(',', $productIds);
    }
}

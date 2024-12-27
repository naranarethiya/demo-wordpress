<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

interface ProductInventoryCachingServiceContract
{
    /**
     * Refreshes all inventory-related cache for the given product IDs.
     *
     * This makes calls to the Commerce APIs.
     *
     * @param string[] $productIds
     */
    public function refreshCache(array $productIds) : void;
}

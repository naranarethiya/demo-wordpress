<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Inventory;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter for converting a product's inventory settings to a {@see Inventory} DTO.
 */
class InventoryAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a product's inventory settings into a {@see Inventory} DTO.
     *
     * @param Product|null $product
     * @return Inventory|null
     */
    public function convertToSource(Product $product = null) : ?Inventory
    {
        if (! $product) {
            return null;
        }

        return new Inventory([
            'externalService' => true,
            'tracking'        => $product->hasStockManagementEnabled(),
        ]);
    }

    /**
     * @inerhitDoc
     */
    public function convertFromSource()
    {
        // no-op for now
    }
}

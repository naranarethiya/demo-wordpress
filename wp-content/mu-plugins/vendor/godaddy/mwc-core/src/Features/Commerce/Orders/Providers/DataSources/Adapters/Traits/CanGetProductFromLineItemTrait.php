<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

trait CanGetProductFromLineItemTrait
{
    /**
     * Attempts to adapt the line item's WooCommerce product into a Core {@see Product} instance.
     *
     * @param LineItem $lineItem
     *
     * @return Product|null
     */
    protected function getProductFromLineItem(LineItem $lineItem) : ?Product
    {
        if (! $wooProduct = $lineItem->getProduct()) {
            return null;
        }

        try {
            return ProductAdapter::getNewInstance($wooProduct)->convertFromSource();
        } catch (Exception $e) {
            return null;
        }
    }
}

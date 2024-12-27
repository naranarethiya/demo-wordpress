<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Base contract & individual operations for product levels.
 */
interface LevelOperationContract
{
    /**
     * Gets the product.
     *
     * @return Product
     */
    public function getProduct() : Product;

    /**
     * Sets the product for this operation.
     *
     * @param Product $value
     *
     * @return $this
     */
    public function setProduct(Product $value) : self;
}

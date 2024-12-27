<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\LevelOperationContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

abstract class AbstractLevelOperation implements LevelOperationContract
{
    protected Product $product;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct() : Product
    {
        return $this->product;
    }

    /**
     * {@inheritDoc}
     */
    public function setProduct(Product $value) : AbstractLevelOperation
    {
        $this->product = $value;

        return $this;
    }
}

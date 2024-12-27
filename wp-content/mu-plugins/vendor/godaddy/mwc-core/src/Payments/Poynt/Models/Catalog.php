<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasRemoteResourceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * The catalog model.
 */
class Catalog extends AbstractModel
{
    use HasLabelTrait;
    use HasRemoteResourceTrait;

    /** @var Category[] */
    protected $categories = [];

    /** @var Product[] */
    protected $products = [];

    /**
     * Gets the categories.
     *
     * @return Category[]
     */
    public function getCategories() : array
    {
        return $this->categories;
    }

    /**
     * Gets the products.
     *
     * @return Product[]
     */
    public function getProducts() : array
    {
        return $this->products;
    }

    /**
     * Sets the categories.
     *
     * @param Category[] $value
     * @return self
     */
    public function setCategories(array $value) : Catalog
    {
        $this->categories = $value;

        return $this;
    }

    /**
     * Sets the products.
     *
     * @param Product[] $value
     * @return self
     */
    public function setProducts(array $value) : Catalog
    {
        $this->products = $value;

        return $this;
    }
}

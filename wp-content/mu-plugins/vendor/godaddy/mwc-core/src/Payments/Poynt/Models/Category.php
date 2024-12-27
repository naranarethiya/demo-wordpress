<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasRemoteResourceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class Category extends AbstractModel
{
    use HasLabelTrait;
    use HasRemoteResourceTrait;

    /** @var Product[] */
    protected $products = [];

    /**
     * Gets the products.
     *
     * @return array|null
     */
    public function getProducts() : array
    {
        return $this->products ?? [];
    }

    /**
     * Sets the products.
     *
     * @param array $products
     * @return self
     */
    public function setProducts(array $products) : self
    {
        $this->products = $products;

        return $this;
    }
}

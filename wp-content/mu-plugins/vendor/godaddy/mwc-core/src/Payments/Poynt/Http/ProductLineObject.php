<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WC_Product;

/**
 * This class represents a product and its quantity. It may represent a cart or order item.
 *
 * Right now, it is used as input for the WalletRequestController on single product pages.
 */
class ProductLineObject
{
    use CanGetNewInstanceTrait;
    use CanConvertToArrayTrait;

    /** @var WC_Product */
    protected $product;

    /** @var float */
    protected $quantity = 0;

    /**
     * Gets the product.
     *
     * @return WC_Product
     */
    public function getProduct() : WC_Product
    {
        return $this->product;
    }

    /**
     * Sets the product.
     *
     * @param WC_Product $value
     *
     * @return $this
     */
    public function setProduct(WC_Product $value) : ProductLineObject
    {
        $this->product = $value;

        return $this;
    }

    /**
     * Gets the quantity.
     *
     * @return float
     */
    public function getQuantity() : float
    {
        return $this->quantity;
    }

    /**
     * Sets the quantity.
     *
     * @param float $value
     *
     * @return $this
     */
    public function setQuantity(float $value) : ProductLineObject
    {
        $this->quantity = $value;

        return $this;
    }
}

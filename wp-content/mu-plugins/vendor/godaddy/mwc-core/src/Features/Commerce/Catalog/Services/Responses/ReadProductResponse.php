<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadProductResponseContract;

/**
 * Response object for a read product request.
 *
 * @method static static getNewInstance(ProductBase $product)
 */
class ReadProductResponse implements ReadProductResponseContract
{
    use CanGetNewInstanceTrait;

    /** @var ProductBase */
    protected ProductBase $product;

    /**
     * Constructor.
     *
     * @param ProductBase $product
     */
    public function __construct(ProductBase $product)
    {
        $this->product = $product;
    }

    /**
     * Gets the response product.
     *
     * @return ProductBase
     */
    public function getProduct() : ProductBase
    {
        return $this->product;
    }

    /**
     * Sets the response product.
     *
     * @param ProductBase $product
     * @return $this
     */
    public function setProduct(ProductBase $product) : ReadProductResponse
    {
        $this->product = $product;

        return $this;
    }
}

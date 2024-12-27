<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

/**
 * Contract for a read product response.
 */
interface ReadProductResponseContract
{
    /**
     * Sets the response product.
     *
     * @param ProductBase $product
     * @return ReadProductResponseContract
     */
    public function setProduct(ProductBase $product) : ReadProductResponseContract;

    /**
     * Gets the response product.
     *
     * @return ProductBase
     */
    public function getProduct() : ProductBase;
}

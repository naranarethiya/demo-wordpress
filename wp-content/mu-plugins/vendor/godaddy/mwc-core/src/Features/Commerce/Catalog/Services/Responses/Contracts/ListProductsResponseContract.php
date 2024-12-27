<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;

/**
 * Contract for responses when listing products.
 */
interface ListProductsResponseContract
{
    /**
     * Sets product associations for the response.
     *
     * @param ProductAssociation[] $products
     *
     * @return ListProductsResponseContract
     */
    public function setProducts(array $products) : ListProductsResponseContract;

    /**
     * Gets product associations for the response.
     *
     * @return ProductAssociation[]
     */
    public function getProducts() : array;

    /**
     * Gets the local IDs for the products.
     *
     * @return int[]
     */
    public function getLocalIds() : array;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;

/**
 * Response object for a list products request.
 */
class ListProductsResponse implements ListProductsResponseContract
{
    /** @var ProductAssociation[] */
    protected array $products;

    /**
     * Sets up the list products response.
     *
     * @param ProductAssociation[] $products
     */
    public function __construct(array $products)
    {
        $this->setProducts($products);
    }

    /**
     * Sets product associations for the response.
     *
     * @param ProductAssociation[] $value
     *
     * @return ListProductsResponseContract
     */
    public function setProducts(array $value) : ListProductsResponseContract
    {
        $this->products = $value;

        return $this;
    }

    /**
     * Gets product associations for the response.
     *
     * @return ProductAssociation[]
     */
    public function getProducts() : array
    {
        return $this->products;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalIds() : array
    {
        return array_column($this->getProducts(), 'localId');
    }
}

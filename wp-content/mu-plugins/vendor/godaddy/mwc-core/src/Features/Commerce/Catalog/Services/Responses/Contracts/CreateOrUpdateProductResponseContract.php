<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

/**
 * Contract for responses when creating or updating a product.
 */
interface CreateOrUpdateProductResponseContract
{
    /**
     * Gets the product's remote UUID.
     *
     * @return non-empty-string
     */
    public function getRemoteId() : string;

    /**
     * Gets the product returned via the response.
     *
     * @return ProductBase
     */
    public function getProduct() : ProductBase;
}

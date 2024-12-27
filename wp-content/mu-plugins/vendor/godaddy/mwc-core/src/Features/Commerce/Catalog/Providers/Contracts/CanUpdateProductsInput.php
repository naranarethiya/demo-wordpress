<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

/**
 * Contract for handlers that can update.
 */
interface CanUpdateProductsInput
{
    /**
     * Updates a product.
     *
     * @param UpdateProductInput $input
     * @return ProductBase
     * @throws CommerceExceptionContract
     */
    public function update(UpdateProductInput $input) : ProductBase;
}

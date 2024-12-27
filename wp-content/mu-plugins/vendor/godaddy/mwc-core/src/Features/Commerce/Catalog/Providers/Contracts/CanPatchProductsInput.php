<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\PatchProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

/**
 * Contract for handlers that can patch.
 */
interface CanPatchProductsInput
{
    /**
     * Patches a product.
     *
     * @param PatchProductInput $input
     * @return ProductBase
     * @throws CommerceExceptionContract
     */
    public function patch(PatchProductInput $input) : ProductBase;
}

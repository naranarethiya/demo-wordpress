<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\CreateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\NotUniqueException;

/**
 * Contract for handlers that can create.
 */
interface CanCreateProductsContract
{
    /**
     * Creates a product.
     *
     * @param CreateProductInput $input
     * @return ProductBase
     * @throws CommerceExceptionContract|NotUniqueException
     */
    public function create(CreateProductInput $input) : ProductBase;
}

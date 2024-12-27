<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

/**
 * Contract for handlers that handle products.
 */
interface ProductsGatewayContract extends CanCreateProductsContract, CanUpdateProductsInput, CanReadProductsContract, CanListProductsContract, CanPatchProductsInput
{
}

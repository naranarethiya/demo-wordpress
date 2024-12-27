<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ListProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;

/**
 * Contract for gateways that can list products.
 */
interface CanListProductsContract
{
    /**
     * Lists products.
     *
     * @param ListProductsInput $input
     * @return ProductBase[]
     * @throws GatewayRequestException
     */
    public function list(ListProductsInput $input) : array;
}

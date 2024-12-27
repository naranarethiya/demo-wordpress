<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

/**
 * Contract for providers that have a products' gateway ({@see ProductsGatewayContract}).
 */
interface HasProductsGatewayContract
{
    /**
     * Returns the products' gateway.
     *
     * @return ProductsGatewayContract
     */
    public function products() : ProductsGatewayContract;
}

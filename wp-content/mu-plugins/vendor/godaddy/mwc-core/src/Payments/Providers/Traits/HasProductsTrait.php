<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers\Traits;

use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\ProductsGateway;

trait HasProductsTrait
{
    /**
     * Gets an instance of the Products Gateway.
     *
     * @return ProductsGateway
     */
    public function products() : ProductsGateway
    {
        return new ProductsGateway();
    }
}

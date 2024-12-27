<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\CategoriesGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\ProductsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Gateways\CategoriesGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Gateways\ProductsGateway;

/**
 * GoDaddy Catalog provider.
 */
class CatalogProvider implements CatalogProviderContract
{
    use CanGetNewInstanceTrait;

    /**
     * Returns the {@see ProductsGateway} handler.
     *
     * @return ProductsGatewayContract
     */
    public function products() : ProductsGatewayContract
    {
        return ProductsGateway::getNewInstance();
    }

    /**
     * Returns the {@see CategoriesGateway} handler.
     *
     * @return CategoriesGatewayContract
     */
    public function categories() : CategoriesGatewayContract
    {
        return CategoriesGateway::getNewInstance();
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

/**
 * Contract for providers that have a category gateway {@see CategoriesGatewayContract}.
 */
interface HasCategoriesGatewayContract
{
    /**
     * Returns a concrete {@see CategoriesGatewayContract} instance.
     *
     * @return CategoriesGatewayContract
     */
    public function categories() : CategoriesGatewayContract;
}

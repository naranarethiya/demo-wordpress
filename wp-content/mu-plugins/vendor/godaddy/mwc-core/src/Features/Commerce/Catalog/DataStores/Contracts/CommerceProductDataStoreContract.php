<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Traits\HasProductPlatformDataStoreCrudTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotCreatableException;
use WC_Product;

/**
 * Describes classes that act as product data stores with Commerce CRUD capabilities {@see HasProductPlatformDataStoreCrudTrait}.
 */
interface CommerceProductDataStoreContract
{
    /**
     * Applies any necessary adjustments to the {@see WC_Product} object, then creates or updates it in the remote platform.
     *
     * @param WC_Product $product
     * @return void
     * @throws GatewayRequestException|ProductNotCreatableException|Exception
     */
    public function transformAndWriteProduct(WC_Product $product) : void;
}

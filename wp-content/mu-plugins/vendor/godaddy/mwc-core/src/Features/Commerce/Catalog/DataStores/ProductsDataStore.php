<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Contracts\CommerceProductDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Traits\HasProductPlatformDataStoreCrudTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\MapAssetsHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use WC_Product_Data_Store_CPT;

/**
 * Commerce Catalog products data store for standard products.
 *
 * A WooCommerce data store for products to replace the default data store to enable read and write operations with the Commerce API.
 */
class ProductsDataStore extends WC_Product_Data_Store_CPT implements CommerceProductDataStoreContract
{
    use HasProductPlatformDataStoreCrudTrait;

    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    /**
     * Constructs the data store.
     *
     * @param ProductsServiceContract $productsService
     * @param MapAssetsHelper $mapAssetsHelper
     */
    public function __construct(ProductsServiceContract $productsService, MapAssetsHelper $mapAssetsHelper)
    {
        $this->productsService = $productsService;
        $this->mapAssetsHelper = $mapAssetsHelper;
    }
}

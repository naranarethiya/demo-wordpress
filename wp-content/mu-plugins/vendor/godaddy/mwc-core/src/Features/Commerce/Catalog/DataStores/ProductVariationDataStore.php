<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Contracts\CommerceProductDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Traits\HasProductPlatformDataStoreCrudTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\MapAssetsHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use WC_Product_Variation;
use WC_Product_Variation_Data_Store_CPT;

/**
 * Commerce Catalog products data store for variant products.
 *
 * A WooCommerce data store for product variations to replace the default data store to enable read and write operations with the Commerce API.
 */
class ProductVariationDataStore extends WC_Product_Variation_Data_Store_CPT implements CommerceProductDataStoreContract
{
    use HasProductPlatformDataStoreCrudTrait;

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

    /**
     * @param WC_Product_Variation $product
     * @return void
     * @throws Exception
     */
    public function read(&$product) : void
    {
        $filter = Register::filter()
            ->setGroup('woocommerce_product_variation_title')
            ->setHandler([$this, 'filterVariationTitle'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(4);

        $filter->execute();

        parent::read($product);

        $filter->deregister();
    }

    /**
     * Returns the un-modified product name, as it's set in the database.
     *
     * This fixes an infinite loop where WooCommerce reads a variant, then modifies the title and writes the change to
     * the database in such a way that it doesn't make it to the API, resulting in the database write happening over
     * and over again. By applying a filter here to return the original database value, we stop that unexpected
     * write from happening, which also fixes the infinite loop.
     * {@see parent::read()} -- specifically the WPDB write at the end.
     * Details can be found in MWC-16079.
     *
     * @internal
     *
     * @param string|mixed $new_title
     * @param WC_Product_Variation|mixed $product
     * @param string|mixed $title_base
     * @param string|mixed $title_suffix
     * @return string|mixed
     */
    public function filterVariationTitle($new_title, $product, $title_base, $title_suffix)
    {
        if ($product instanceof WC_Product_Variation) {
            return $product->get_name();
        } else {
            return $new_title;
        }
    }
}

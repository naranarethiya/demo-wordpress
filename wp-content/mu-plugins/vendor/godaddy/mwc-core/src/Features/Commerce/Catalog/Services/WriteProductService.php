<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Contracts\CommerceProductDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\ProductsDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotCreatableException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\ResourceWriteServiceContract;
use WC_Data_Store;
use WC_Product;
use WC_Product_Data_Store_Interface;
use WC_Product_External;
use WC_Product_Grouped;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Service class to facilitate writing a local product object to the remote platform.
 *
 * This class is ultimately a wrapper around {@see ProductsDataStore::transformAndWriteProduct()} and {@see ProductsService}. It ensures we route the
 * creation through the appropriate data store class, which also ensures inventory data gets written at the same time.
 * Additionally this class will perform a few prerequisite checks to ensure the product is eligible to be written in the
 * first place.
 */
class WriteProductService implements ResourceWriteServiceContract
{
    /**
     * Writes the supplied local products (by local ID) to the remote platform.
     *
     * @param int[] $localIds Array of WooCommerce product IDs
     * @return void
     * @throws GatewayRequestException|CommerceException|ProductNotCreatableException|Exception
     */
    public function writeByLocalIds(array $localIds) : void
    {
        // removes some unneeded clauses from the below database query to keep things simple
        $this->getProductsQueryFilter()->execute();

        /** @var WC_Product[]|WC_Product_External[]|WC_Product_Grouped[]|WC_Product_Variable[]|WC_Product_Variation[] $products */
        $products = CatalogIntegration::withoutReads(function () use ($localIds) {
            return ProductsRepository::query([
                'limit'   => count($localIds),
                'include' => $localIds,
                'type'    => ['variation', 'simple', 'variable'],
                'orderby' => 'ID',
                'order'   => 'ASC',
            ]);
        });

        // removes our filter on the database query
        $this->getProductsQueryFilter()->deregister();

        foreach ($products as $product) {
            $this->write($product);
        }
    }

    /**
     * Gets the filter registration on the product query arguments.
     *
     * @see \WC_Product_Data_Store_CPT::get_wp_query_args()
     *
     * @return RegisterFilter
     */
    protected function getProductsQueryFilter() : RegisterFilter
    {
        return Register::filter()
            ->setGroup('woocommerce_product_data_store_cpt_get_products_query')
            ->setHandler([$this, 'filterProductsQueryArgs'])
            ->setArgumentsCount(3);
    }

    /**
     * Filters the query arguments to remove the tax query.
     *
     * In our query ({@see static::writeByLocalIds()}) we don't need to do any checks on the `product_type` taxonomy.
     * Just filtering by `wp_posts.post_type` is fine. Removing the tax query has some performance benefits.
     *
     * @internal
     *
     * @param array<string, mixed>|mixed $wp_query_args
     * @param array<string, mixed>|mixed $query_vars
     * @param WC_Product_Data_Store_Interface|mixed $data_store
     * @return array<string, mixed>|mixed
     */
    public function filterProductsQueryArgs($wp_query_args, $query_vars, $data_store)
    {
        if (is_array($wp_query_args) && isset($wp_query_args['tax_query'])) {
            unset($wp_query_args['tax_query']);
        }

        return $wp_query_args;
    }

    /**
     * Writes the local product to the remote platform.
     *
     * @param object|WC_Product $localResource
     * @return void
     * @throws GatewayRequestException|CommerceException|ProductNotCreatableException|Exception
     */
    public function write(object $localResource)
    {
        if (! $localResource instanceof WC_Product) {
            throw new CommerceException('Invalid local resource.');
        }

        $dataStore = $localResource->get_data_store();
        if (! $this->isValidCommerceDataStore($dataStore)) {
            throw new ProductNotCreatableException('Unsupported data store class.');
        }

        $dataStore->transformAndWriteProduct($localResource);
    }

    /**
     * Determines if the provided data store class is a valid Commerce product data store {@see CommerceProductDataStoreContract}.
     *
     * @param object $dataStoreClass
     * @return bool
     * @phpstan-assert-if-true WC_Data_Store&CommerceProductDataStoreContract $dataStoreClass
     */
    protected function isValidCommerceDataStore(object $dataStoreClass) : bool
    {
        return $dataStoreClass instanceof WC_Data_Store
            && $dataStoreClass->has_callable('transformAndWriteProduct')
            && in_array(CommerceProductDataStoreContract::class, TypeHelper::array(class_implements($dataStoreClass->get_current_class_name()), []), true);
    }
}

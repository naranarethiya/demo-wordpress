<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Contracts\CommerceProductDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Traits\HasProductPlatformDataStoreCrudTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\MapAssetsHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\SimpleMoneyAdapter;
use WC_Product;
use WC_Product_Variable_Data_Store_CPT;

/**
 * Commerce Catalog products data store for variable products (products that contain variants).
 *
 * A WooCommerce data store for variable products to replace the default data store to enable read and write operations with the Commerce API.
 */
class VariableProductDataStore extends WC_Product_Variable_Data_Store_CPT implements CommerceProductDataStoreContract
{
    public const META_LOOKUP_TABLE = 'wc_product_meta_lookup';

    use HasProductPlatformDataStoreCrudTrait;

    protected BatchListProductsByLocalIdService $batchListProductsByLocalIdService;

    /**
     * Constructs the data store.
     *
     * @param ProductsServiceContract $productsService
     * @param MapAssetsHelper $mapAssetsHelper
     * @param BatchListProductsByLocalIdService $batchListProductsByLocalIdService
     */
    public function __construct(ProductsServiceContract $productsService, MapAssetsHelper $mapAssetsHelper, BatchListProductsByLocalIdService $batchListProductsByLocalIdService)
    {
        $this->productsService = $productsService;
        $this->mapAssetsHelper = $mapAssetsHelper;
        $this->batchListProductsByLocalIdService = $batchListProductsByLocalIdService;
    }

    /**
     * {@inheritDoc}
     */
    public function read_children(&$product, $force_read = false) // @phpstan-ignore-line
    {
        $children = parent::read_children($product, $force_read); // @phpstan-ignore-line
        $childrenIds = TypeHelper::arrayOfIntegers(ArrayHelper::get($children, 'all', []));

        if (! empty($childrenIds)) {
            // pre-warm the cache for these products
            $this->batchListProductsByLocalIdService->batchListByLocalIds($childrenIds);
        }

        return $children;
    }

    /**
     * Gets certain data for the lookup table from the API directly.
     *
     * This is primarily overridden, so we can modify the "price" data for
     * variable products. We fetch the prices of all variants from the API
     * instead of the local DB.
     *
     * @param int $id ID of object to update.
     * @param string $table Lookup table name.
     * @return array<string, mixed>
     */
    protected function get_data_for_lookup_table($id, $table)
    {
        $filter = $this->getFilterForVariationPrices();

        if (self::META_LOOKUP_TABLE === $table) {
            try {
                // override the call to `get_post_meta( $id, '_price', false );`
                $filter->execute();
            } catch (Exception $e) {
                // do nothing.
            }
        }

        /* @phpstan-ignore-next-line */
        $data = parent::get_data_for_lookup_table($id, $table);

        if (self::META_LOOKUP_TABLE === $table) {
            try {
                $filter->deregister();
            } catch (Exception $e) {
                // do nothing.
            }
        }

        return $data;
    }

    /**
     * Gets the filter to override the call to `get_post_meta( $id, '_price', false );`.
     *
     * @return RegisterFilter
     */
    protected function getFilterForVariationPrices() : RegisterFilter
    {
        return Register::filter()
            ->setGroup('get_post_metadata')
            ->setHandler([$this, 'filterPriceMetaForVariations'])
            ->setPriority(10)
            ->setArgumentsCount(5);
    }

    /**
     * Filters the call to get variation price meta so it's read from the API
     * instead of the local DB.
     *
     * @param null|mixed $value
     * @param int|mixed $objectId
     * @param string|mixed $metaKey
     * @param bool|mixed $single
     * @param string|mixed $metaType
     * @return mixed
     */
    public function filterPriceMetaForVariations($value, $objectId, $metaKey, $single, $metaType)
    {
        // we only want to filter _price metadata when NOT retrieving a single value
        if ('_price' !== $metaKey || false !== $single || ! $objectId) {
            return $value;
        }

        $children = $this->getLocalProductVariationIds($objectId);

        if (empty($children['all'])) {
            return $value;
        }

        $productAssociations = $this->batchListProductsByLocalIdService->batchListByLocalIds($children['all']);
        $prices = $this->maybeCompilePricesForRemoteVariations($productAssociations);

        return $prices ?: $value;
    }

    /**
     * Gets local product variation IDs for a given product ID.
     *
     * @param mixed $objectId
     * @return array<string, array<int>>
     */
    protected function getLocalProductVariationIds($objectId) : array
    {
        $product = wc_get_product($objectId);

        return $product instanceof WC_Product ? $this->read_children($product) : [];
    }

    /**
     * Attempts to compile prices from remote product variations.
     *
     * @param ProductAssociation[] $productAssociations
     * @return array<float>
     */
    protected function maybeCompilePricesForRemoteVariations(array $productAssociations) : array
    {
        $prices = [];

        $wooCommerceCurrency = WooCommerceRepository::getCurrency();

        foreach ($productAssociations as $productAssociation) {
            $remotePrice = $productAssociation->remoteResource->salePrice ?: $productAssociation->remoteResource->price;
            if ($remotePrice) {
                $corePrice = SimpleMoneyAdapter::getNewInstance()->convertFromSimpleMoney($remotePrice);
                $prices[] = (new CurrencyAmountAdapter($corePrice->getAmount(), $wooCommerceCurrency))->convertToSource($corePrice);
            }
        }

        sort($prices, SORT_NUMERIC);

        return $prices;
    }
}

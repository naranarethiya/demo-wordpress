<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters;

use DateTime;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Product\ProductAdapter as CommonProductAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\TaxonomyTermAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Dimensions;
use GoDaddy\WordPress\MWC\Common\Models\Products\Product as CommonProduct;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Core product adapter.
 *
 * Converts between a native core product object and a WooCommerce product object.
 *
 * @property WC_Product $source
 * @method static static getNewInstance(WC_Product $product)
 */
class ProductAdapter extends CommonProductAdapter
{
    use CanGetNewInstanceTrait;

    /** @var string the Marketplaces listings meta key */
    public const MARKETPLACES_LISTINGS_META_KEY = '_marketplaces_listings';

    /** @var string the Marketplaces brand meta key */
    public const MARKETPLACES_BRAND_META_KEY = '_marketplaces_brand';

    /** @var string the Marketplaces condition meta key */
    public const MARKETPLACES_CONDITION_META_KEY = '_marketplaces_condition';

    /** @var string the Marketplaces Global Trade Item Number (GTIN) meta key */
    public const MARKETPLACES_GTIN_META_KEY = '_marketplaces_gtin';

    /** @var string the Marketplaces Manufacturer Part Number (MPN) meta key */
    public const MARKETPLACES_MPN_META_KEY = '_marketplaces_mpn';

    /** @var string the Marketplaces Google Product ID */
    public const MARKETPLACES_GOOGLE_PRODUCT_ID = '_marketplaces_google_product_id';

    /** @var string the default tax category */
    public const TAX_CATEGORY_STANDARD = 'standard';

    /** @var class-string<Product> the product class name */
    protected $productClass = Product::class;

    /**
     * Adapts the product from source.
     *
     * @return Product
     * @throws Exception
     */
    public function convertFromSource() : CommonProduct
    {
        /** @var Product $product */
        $product = parent::convertFromSource();

        $product->setSlug(TypeHelper::string($this->source->get_slug(), ''));
        $product->setUrl($this->source->get_permalink());
        $product->setDescription(TypeHelper::string($this->source->get_description(), ''));
        $product->setPassword(TypeHelper::string($this->source->get_post_password(), ''));
        $product->setTaxCategory($this->convertTaxCategoryFromSource());

        if ($parentId = TypeHelper::int($this->source->get_parent_id(), 0)) {
            $product->setParentId($parentId);
        }

        $product->setVariants($this->convertVariantsFromSource());
        $product->setDimensions($this->convertDimensionsFromSource());
        $product->setIsVirtual((bool) $this->source->is_virtual());
        $product->setIsDownloadable((bool) $this->source->is_downloadable());
        $product->setIsPurchasable((bool) $this->source->is_purchasable());

        $this->convertDatesFromSource($product);
        $this->convertDownloadablesFromSource($product);
        $this->convertCategoriesFromSource($product);
        $this->convertImageIdsFromSource($product);
        $this->convertMarketplacesDataFromSource($product);
        $this->convertStockPropertiesFromSource($product);
        $this->convertGlobalUniqueIdFromSource($product);

        return $product;
    }

    /**
     * Converts a core native product object into a WooCommerce product object.
     *
     * @param Product|null $product native core product object to convert
     * @param bool $getNewInstance whether to get a fresh instance of a WC_Product
     * @return WC_Product WooCommerce product object
     * @throws Exception
     */
    public function convertToSource($product = null, bool $getNewInstance = true) : WC_Product
    {
        $this->source = parent::convertToSource($product, $getNewInstance);

        if ($product) {
            if ($parentId = $product->getParentId()) {
                $this->source->set_parent_id($parentId);
            }

            /* @phpstan-ignore-next-line `WC_Product` incorrectly documented the value of `set_post_password()` as int instead of string */
            $this->source->set_post_password($product->getPassword() ?: '');

            $this->source->set_tax_class($this->convertTaxCategoryToSource($product));

            $this->convertCategoriesToSource($this->source, $product);

            if ($backordersAllowed = $product->getBackordersAllowed()) {
                $this->source->set_backorders($backordersAllowed);
            }

            $this->convertImageIdsToSource($this->source, $product);
            $this->convertGlobalUniqueIdToSource($this->source, $product);
        }

        $this->convertMarketplacesDataToSource($this->source, $product);

        return $this->source;
    }

    /**
     * Converts product dates from source.
     *
     * @NOTE WooCommerce methods return dates in the local site timezone, so we need to adapt to UTC.
     *
     * @param Product $product
     * @return void
     * @throws Exception
     */
    protected function convertDatesFromSource(Product $product) : void
    {
        if ($createdAt = $this->source->get_date_created()) {
            $product->setCreatedAt(new DateTime($createdAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')));
        }

        if ($updatedAt = $this->source->get_date_modified()) {
            $product->setUpdatedAt(new DateTime($updatedAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')));
        }
    }

    /**
     * Converts stock properties from the source product.
     *
     * @param Product $product
     * @return void
     */
    protected function convertStockPropertiesFromSource(Product $product) : void
    {
        $isStockManaged = (bool) $this->source->get_manage_stock();

        $product->setStockManagementEnabled($isStockManaged);

        if ($isStockManaged && ($currentStock = $this->convertNumberToFloat($this->source->get_stock_quantity()))) {
            $product->setCurrentStock($currentStock);
        }

        if ($isStockManaged && ($backordersAllowed = $this->source->get_backorders())) {
            $product->setBackordersAllowed($backordersAllowed);
        }

        if ($isStockManaged && ($lowStockAmount = $this->source->get_low_stock_amount())) {
            $product->setLowStockThreshold(TypeHelper::float($lowStockAmount, 0.0));
        }

        if (! $isStockManaged && ($stockStatus = $this->source->get_stock_status())) {
            $product->setStockStatus(TypeHelper::string($stockStatus, ''));
        }
    }

    /**
     * Gets a global unique ID value from the product meta.
     *
     * @param Product $product
     * @return void
     */
    protected function convertGlobalUniqueIdFromSource(Product $product) : void
    {
        if ($globalUniqueId = WooCommerceGlobalUniqueIdProvider::getNewInstance()->getGlobalUniqueId($this->source)) {
            $product->setGlobalUniqueId($globalUniqueId);
        }
    }

    /**
     * Converts downloadables from the source product.
     *
     * @param Product $product
     * @return void
     */
    protected function convertDownloadablesFromSource(Product $product) : void
    {
        $downloadables = [];

        foreach ($this->source->get_downloads() as $productDownload) {
            $downloadables[] = DownloadableAdapter::getNewInstance($productDownload)->convertFromSource();
        }

        if (! empty($downloadables)) {
            $product->setDownloadables($downloadables);
        }
    }

    /**
     * Converts source category IDs to native product category objects.
     *
     * @param Product $product
     * @return void
     */
    protected function convertCategoriesFromSource(Product $product) : void
    {
        $categories = [];

        foreach ($this->source->get_category_ids() as $categoryId) {
            // NOTE: this is not an N+1 problem, as the terms were already loaded into memory when the WC product was fetched
            if ($term = TermsRepository::getTerm($categoryId, CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY)) {
                $categories[] = TaxonomyTermAdapter::getNewInstance($term)->convertFromSource();
            }
        }

        $product->setCategories($categories);
    }

    /**
     * Converts WooCommerce image IDs to native product image properties.
     *
     * @NOTE The reason why we adapt only image IDs and not whole images is to reduce unnecessary queries to fetch all the data for each image.
     *
     * @param Product $product
     * @return void
     */
    protected function convertImageIdsFromSource(Product $product) : void
    {
        if ($featuredImageId = $this->source->get_image_id()) {
            $product->setMainImageId((int) $featuredImageId);
        }

        if ($galleryImageIds = $this->source->get_gallery_image_ids()) {
            $product->setImageIds(array_map('intval', $galleryImageIds));
        }
    }

    /**
     * Converts image IDs on the core Product model to the WC_Product model.
     *
     * @param WC_Product $wcProduct
     * @param Product $product
     * @return void
     */
    protected function convertImageIdsToSource(WC_Product $wcProduct, Product $product) : void
    {
        if ($mainImageId = $product->getMainImageId()) {
            $wcProduct->set_image_id($mainImageId);
        }
        if ($galleryIds = $product->getImageIds()) {
            $wcProduct->set_gallery_image_ids($galleryIds);
        }
    }

    /**
     * Converts product dimensions from source.
     *
     * @return Dimensions
     */
    protected function convertDimensionsFromSource() : Dimensions
    {
        $dimensions = new Dimensions();

        foreach (['width', 'height', 'length'] as $dimension) {
            $getDimension = 'get_'.$dimension;
            $setDimension = 'set'.ucfirst($dimension);

            if ($value = $this->convertNumberToFloat($this->source->{$getDimension}('edit'))) {
                $dimensions->{$setDimension}($value);
            }
        }

        if ($unit = TypeHelper::string(get_option('woocommerce_dimension_unit'), '')) {
            $dimensions->setUnitOfMeasurement($unit);
        }

        return $dimensions;
    }

    /**
     * Converts source product variations into native product variants, if any.
     *
     * @return Product[]
     * @throws Exception
     */
    protected function convertVariantsFromSource() : array
    {
        $variants = [];

        if ($variations = $this->source->get_children()) {
            foreach ($variations as $variationId) {
                if ($source = ProductsRepository::get($variationId)) {
                    $productVariationAdapter = static::getNewInstance($source);
                    $variant = $productVariationAdapter->convertFromSource();
                    $variant->setAttributeData($productVariationAdapter->convertVariantAttributesFromSource());
                    $variants[] = $variant;
                }
            }
        }

        return $variants;
    }

    /**
     * Convert variant attributes to a plain array. Should only be called when $this->source is WC_Product_Variant,
     * otherwise it may throw.
     *
     * @return array<int, array<string, string>>
     */
    protected function convertVariantAttributesFromSource() : array
    {
        $variationAttributes = $this->source->get_attributes();

        return array_map(function (string $variationAttributeKey) {
            /*
             * {llessa 2022-09-28} Only converts necessary attribute data available in events for Marketplaces product sync.
             * If we make changes to this implementation, it should have at least the properties below to keep backward compatibility.
             */
            return [
                'key'   => $variationAttributeKey,
                'label' => $this->source->get_attribute($variationAttributeKey),
            ];
        }, array_keys($variationAttributes));
    }

    /**
     * Ensures that a number will be adapted to a float.
     *
     * @param string|int|float|mixed $number
     * @return float|null
     */
    protected function convertNumberToFloat($number) : ?float
    {
        return is_numeric($number) ? (float) $number : null;
    }

    /**
     * Converts Marketplaces listings' information from a WC Product object to a core product instance.
     *
     * @param Product $product
     * @return void
     */
    protected function convertMarketplacesDataFromSource(Product $product) : void
    {
        $marketplacesListingsMeta = TypeHelper::array($this->source->get_meta(static::MARKETPLACES_LISTINGS_META_KEY), []);

        if (! empty($marketplacesListingsMeta)) {
            $listings = [];

            foreach ($marketplacesListingsMeta as $marketplacesListingMeta) {
                $listings[] = (new Listing())
                    ->setProperties(array_filter(TypeHelper::array($marketplacesListingMeta, [])));
            }

            $product->setMarketplacesListings($listings);
        }

        $product->setMarketplacesBrand(TypeHelper::string($this->source->get_meta(static::MARKETPLACES_BRAND_META_KEY), '') ?: null);
        $product->setMarketplacesCondition(TypeHelper::string($this->source->get_meta(static::MARKETPLACES_CONDITION_META_KEY), '') ?: null);
        $product->setMarketplacesGtin(TypeHelper::string($this->source->get_meta(static::MARKETPLACES_GTIN_META_KEY), '') ?: null);
        $product->setMarketplacesMpn(TypeHelper::string($this->source->get_meta(static::MARKETPLACES_MPN_META_KEY), '') ?: null);
        $product->setMarketplacesGoogleProductId(TypeHelper::string($this->source->get_meta(static::MARKETPLACES_GOOGLE_PRODUCT_ID), '') ?: null);
    }

    /**
     * Converts native product tax class values to tax categories.
     *
     * Defaults to 'standard'.
     *
     * @return string
     */
    protected function convertTaxCategoryFromSource() : string
    {
        return TypeHelper::string($this->source->get_tax_class(), static::TAX_CATEGORY_STANDARD) ?: static::TAX_CATEGORY_STANDARD;
    }

    /**
     * Converts an array of native product categories into an array of WooCommerce product category IDs.
     *
     * @param WC_Product $wcProduct
     * @param Product $product
     * @return void
     */
    protected function convertCategoriesToSource(WC_Product $wcProduct, Product $product)
    {
        $categoryIds = array_map(function (Term $term) {
            return $term->getId();
        }, $product->getCategories());

        $wcProduct->set_category_ids($categoryIds);
    }

    /**
     * Converts Marketplaces listings' information from a core product object to the WC Product metadata.
     *
     * @param WC_Product $wcProduct
     * @param null|Product $product
     * @return void
     */
    protected function convertMarketplacesDataToSource(WC_Product $wcProduct, ?Product $product = null) : void
    {
        if ($product) {
            $listings = $product->getMarketplacesListings();
            $marketplacesListingsMeta = [];

            foreach ($listings as $listing) {
                $marketplacesListingsMeta[] = $listing->toArray();
            }

            $wcProduct->update_meta_data(static::MARKETPLACES_LISTINGS_META_KEY, $marketplacesListingsMeta);
            $wcProduct->update_meta_data(static::MARKETPLACES_BRAND_META_KEY, $product->getMarketplacesBrand() ?: '');
            $wcProduct->update_meta_data(static::MARKETPLACES_CONDITION_META_KEY, $product->getMarketplacesCondition() ?: '');
            $wcProduct->update_meta_data(static::MARKETPLACES_GTIN_META_KEY, $product->getMarketplacesGtin() ?: '');
            $wcProduct->update_meta_data(static::MARKETPLACES_MPN_META_KEY, $product->getMarketplacesMpn() ?: '');
            $wcProduct->update_meta_data(static::MARKETPLACES_GOOGLE_PRODUCT_ID, $product->getMarketplacesGoogleProductId() ?: '');
        }
    }

    /**
     * Converts the global unique ID value to source.
     *
     * @param WC_Product $wcProduct
     * @param Product $product
     * @return void
     */
    protected function convertGlobalUniqueIdToSource(WC_Product $wcProduct, Product $product) : void
    {
        if ($globalUniqueId = $product->getGlobalUniqueId()) {
            WooCommerceGlobalUniqueIdProvider::getNewInstance()->setGlobalUniqueId($wcProduct, $globalUniqueId);
        }
    }

    /**
     * Converts the native tax category into a WooCommerce tax class.
     *
     * If a 'standard' tax category is set, use an empty string for WooCommerce (intended empty value).
     *
     * @param Product $product
     * @return string
     */
    protected function convertTaxCategoryToSource(Product $product) : string
    {
        $taxCategory = $product->getTaxCategory();

        if (static::TAX_CATEGORY_STANDARD === $taxCategory) {
            return '';
        }

        return TypeHelper::string($taxCategory, '');
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use DateTime;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringRemoteIdentifierContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Repositories\Exceptions\WordPressRepositoryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringRemoteIdentifierTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VariantOptionMapping;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\SimpleMoneyAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter to convert between a native {@see Product model} and a {@see ProductBase} DTO.
 *
 * @method static static getNewInstance(ProductsMappingServiceContract $productMappingService, ProductMapRepository $productMapRepository)
 */
class ProductBaseAdapter implements DataSourceAdapterContract, HasStringRemoteIdentifierContract
{
    use CanGetNewInstanceTrait;
    use HasStringRemoteIdentifierTrait;

    /** @var ProductsMappingServiceContract */
    protected ProductsMappingServiceContract $productMappingService;

    /** @var ProductMapRepository */
    protected ProductMapRepository $productMapRepository;

    /** @var ProductCategoriesAdapter */
    protected ProductCategoriesAdapter $productCategoriesAdapter;

    /** @var MediaAdapter */
    protected MediaAdapter $mediaAdapter;

    /**
     * Constructor.
     *
     * @param ProductsMappingServiceContract $productsMappingService
     * @param ProductMapRepository $productMapRepository
     * @param ProductCategoriesAdapter $productCategoriesAdapter
     * @param MediaAdapter $mediaAdapter
     */
    public function __construct(
        ProductsMappingServiceContract $productsMappingService,
        ProductMapRepository $productMapRepository,
        ProductCategoriesAdapter $productCategoriesAdapter,
        MediaAdapter $mediaAdapter
    ) {
        $this->productMappingService = $productsMappingService;
        $this->productMapRepository = $productMapRepository;
        $this->productCategoriesAdapter = $productCategoriesAdapter;
        $this->mediaAdapter = $mediaAdapter;
    }

    /**
     * Converts a native {@see Product model} into a {@see ProductBase} DTO.
     *
     * @param Product|null $product Required: local product object.
     * @param ProductBase|null $remoteProduct Optional: remote product object from the platform. This can be supplied
     *                                        in case we need to merge any local and remote data together.
     * @return ProductBase
     * @throws AdapterException|Exception|MissingProductRemoteIdForParentException
     */
    public function convertToSource(Product $product = null, ?ProductBase $remoteProduct = null) : ProductBase
    {
        if (! $product) {
            throw new AdapterException('Cannot convert a null product to a ProductBase DTO');
        }

        $productName = $product->getName();

        if (empty($productName)) {
            throw new AdapterException('Cannot convert a product to a ProductBase DTO without a name');
        }

        $isVariableProduct = 'variable' === $product->getType();
        $parentId = $product->getParentId();

        return new ProductBase([
            'active'                      => $this->convertActiveStatusToSource($product),
            'allowCustomPrice'            => false,
            'assets'                      => $this->mediaAdapter->convertToSource($product, $remoteProduct),
            'brand'                       => $product->getMarketplacesBrand(), // todo: is this correct?
            'categoryIds'                 => $this->convertCategoriesToSource($product),
            'channelIds'                  => [], // Will be set in the request adapter.
            'createdAt'                   => $this->convertDateToSource($product->getCreatedAt()),
            'condition'                   => $this->convertProductConditionToSource($product),
            'description'                 => $product->getDescription() ?: null,
            'ean'                         => null, // We don't have EAN data.
            'externalIds'                 => $this->convertExternalIds($product, $remoteProduct),
            'files'                       => FilesAdapter::getNewInstance()->convertToSource($product),
            'inventory'                   => InventoryAdapter::getNewInstance()->convertToSource($product),
            'manufacturerData'            => null, // We don't have meaningful manufacturer data to send.
            'name'                        => $productName,
            'options'                     => $this->convertOptionsToSource($product),
            'parentId'                    => $this->convertLocalParentIdToRemoteParentUuid($parentId),
            'price'                       => $this->convertPriceToSource($product->getRegularPrice(), ! empty($parentId)), // Variations (products with a parentId) can inherit price from a parent variable product when null.
            'productId'                   => null, // We don't have the remote product ID.
            'purchasable'                 => ! $isVariableProduct, // Parent variable products in Commerce are not purchasable by design.
            'salePrice'                   => $this->convertPriceToSource($product->getSalePrice(), true),
            'shippingWeightAndDimensions' => ShippingWeightAndDimensionsAdapter::getNewInstance()->convertToSource($product),
            'shortCode'                   => null, // We don't have shortcode data.
            'sku'                         => $product->getSku(),
            'taxCategory'                 => $product->getTaxCategory() ?: ProductBase::TAX_CATEGORY_STANDARD,
            'type'                        => $this->convertProductTypeToSource($product),
            'updatedAt'                   => $this->convertDateToSource($product->getUpdatedAt()),
            'variantOptionMapping'        => $this->convertVariantOptionMappingToSource($product),
        ]);
    }

    /**
     * Converts the product's active status.
     *
     * @param Product $product
     * @return bool
     * @throws Exception
     */
    protected function convertActiveStatusToSource(Product $product) : bool
    {
        // We cannot use $product->isPurchasable() here because that checks that the `_price` meta value is not empty, which hasn't been set at this point in time.
        $active = $product->isPublished();

        // Child variations should inherit parent password-protected status.
        if ($active && ($parentId = $product->getParentId())) {
            $parentProduct = ProductsRepository::get($parentId);

            if ($parentProduct) {
                $active = ProductAdapter::getNewInstance($parentProduct)->convertFromSource()->isPublished();
            }
        }

        return $active;
    }

    /**
     * Converts the product condition to source.
     *
     * @param Product $product
     * @return string|null
     */
    protected function convertProductConditionToSource(Product $product) : ?string
    {
        $condition = strtoupper($product->getMarketplacesCondition() ?: '');

        return ArrayHelper::contains([ProductBase::CONDITION_NEW, ProductBase::CONDITION_RECONDITIONED, ProductBase::CONDITION_REFURBISHED, ProductBase::CONDITION_USED], $condition)
            ? $condition
            : null;
    }

    /**
     * Converts the product type.
     *
     * @param Product $product
     * @return string
     */
    protected function convertProductTypeToSource(Product $product) : string
    {
        $type = ProductBase::TYPE_PHYSICAL;

        if ($product->isVirtual()) {
            $type = ProductBase::TYPE_SERVICE;
        }

        if ($product->isDownloadable()) {
            $type = ProductBase::TYPE_DIGITAL;
        }

        return $type;
    }

    /**
     * Exchanges a local (WooCommerce) parent ID for a Commerce UUID.
     *
     * @see ProductPostAdapter::convertRemoteParentUuidToLocalParentId()
     *
     * @param int|null $localParentId
     * @return string|null
     * @throws MissingProductRemoteIdForParentException
     */
    protected function convertLocalParentIdToRemoteParentUuid(?int $localParentId) : ?string
    {
        if (empty($localParentId)) {
            return null;
        }

        $remoteParentId = $this->productMappingService->getRemoteId(Product::getNewInstance()->setId($localParentId));

        if (! $remoteParentId) {
            // throwing an exception here prevents us from incorrectly identifying the product as having no parent in Commerce
            throw new MissingProductRemoteIdForParentException("Failed to retrieve remote ID for parent product {$localParentId}.");
        }

        return $remoteParentId;
    }

    /**
     * Converts the categories to an array of category IDs.
     *
     * @param Product $product
     * @return array<string> category IDs
     * @throws AdapterException
     */
    protected function convertCategoriesToSource(Product $product) : array
    {
        // excludes the `uncategorized` category, as we don't write that to the platform
        $categories = array_filter($product->getCategories(), fn (Term $term) => $term->getName() !== CatalogIntegration::INELIGIBLE_PRODUCT_CATEGORY_NAME);

        return $this->productCategoriesAdapter->convertToSource($categories);
    }

    /**
     * Converts a datetime object to a string using the `Y-m-d\TH:i:s\Z` format and UTC timezone.
     *
     * @param DateTime|null $date
     * @return string|null
     */
    protected function convertDateToSource(?DateTime $date) : ?string
    {
        if (! $date) {
            return null;
        }

        // ensures that the date is in UTC
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Converts the native product's price as {@see CurrencyAmount} into a {@see SimpleMoney} object.
     *
     * In the Commerce API the `product.price` is nullable if one of two conditions are met:
     *   1. The product is a variable product.
     *   2. `product.allowCustomerPrice = true` (we do not currently implement this feature).
     *
     * In other words, in the current implementation, parent products should not send `product.price = null` as
     * this will result in a validation error.
     *
     * For variable products, the API will use the variant's parent's price if its own price is null.
     * We can identify a variant by checking if the product has a parent ID.
     *
     * For reference: {@link https://godaddy.slack.com/archives/C03D3200AA0/p1686606141980469?thread_ts=1686605621.149219&cid=C03D3200AA0}
     *
     * @param CurrencyAmount|null $price
     * @param bool $nullable When `false` and the price is `null`, a zero value is returned.
     * @return SimpleMoney|null
     */
    protected function convertPriceToSource(?CurrencyAmount $price, bool $nullable) : ?SimpleMoney
    {
        if (! $price && ! $nullable) {
            return SimpleMoneyAdapter::getNewInstance()->convertToSourceOrZero($price);
        }

        return SimpleMoneyAdapter::getNewInstance()->convertToSource($price);
    }

    /**
     * Converts source product attributes into Commerce API options.
     *
     * @param Product $product
     * @return AbstractOption[]|null
     */
    protected function convertOptionsToSource(Product $product) : ?array
    {
        $options = null;
        $attributes = $product->getAttributes();

        if ($attributes) {
            $options = [];

            foreach ($attributes as $attribute) {
                if ($option = OptionAdapter::getNewInstance()->convertToSource($attribute)) {
                    $options[] = $option;
                }
            }
        }

        return $options;
    }

    /**
     * Converts attribute mapping to source.
     *
     * @param Product $product
     * @return VariantOptionMapping[]|null
     */
    protected function convertVariantOptionMappingToSource(Product $product) : ?array
    {
        $variantAttributeMapping = $product->getVariantAttributeMapping();

        if (! $variantAttributeMapping) {
            return null;
        }

        $options = [];

        foreach ($variantAttributeMapping as $attributeName => $attributeValue) {
            // skip "Any" attributes
            if (! $attributeValue || '' === $attributeValue->getName()) {
                continue;
            }

            $options[] = VariantOptionMapping::getNewInstance([
                'name'  => $attributeName,
                'value' => $attributeValue->getName(),
            ]);
        }

        // avoid sending an empty array if no concrete options are found
        return ! empty($options) ? $options : null;
    }

    /**
     * Converts a {@see ProductBase} object into a native {@see Product} object.
     *
     * Warning: this does NOT set the local product ID.
     *
     * @param ProductBase|null $productBase
     * @return Product
     * @throws AdapterException|MissingProductLocalIdForParentException|WordPressRepositoryException
     */
    public function convertFromSource(?ProductBase $productBase = null) : Product
    {
        if (! $productBase) {
            throw new AdapterException('A valid ProductBase instance must be supplied.');
        }

        /** @var Product $product core product @phpstan-ignore-next-line PhpStan gets confused between Core and Common objects */
        $product = Product::getNewInstance()
            ->setName($productBase->name)
            ->setDescription($productBase->description ?: '')
            ->setSku($productBase->sku)
            ->setStatus($productBase->active ? 'publish' : 'private')
            ->setType($this->convertProductTypeFromSource($productBase))
            ->setMainImageId($this->mediaAdapter->convertPrimaryAssetFromSource($productBase))
            ->setImageIds($this->mediaAdapter->convertGalleryAssetsFromSource($productBase))
            ->setCategories($this->convertCategoryIdsFromSource($productBase));

        $this->setProductPrices($productBase, $product);

        if ($parentId = $productBase->parentId) {
            $product->setParentId($this->convertRemoteParentUuidToLocalParentId($parentId));
        }

        return $product;
    }

    /**
     * Converts the remote category UUIDs into an array of local {@see Term} objects.
     *
     * @param ProductBase $productBase
     * @return Term[]
     * @throws AdapterException|WordPressRepositoryException
     */
    protected function convertCategoryIdsFromSource(ProductBase $productBase) : array
    {
        return $this->productCategoriesAdapter->convertFromSource($productBase->categoryIds);
    }

    /**
     * Converts the product type into the expected WooCommerce type string.
     *
     * @param ProductBase $productBase
     * @return string
     */
    protected function convertProductTypeFromSource(ProductBase $productBase) : string
    {
        if (! empty($productBase->parentId)) {
            return 'variation';
        } elseif (! empty($productBase->variants)) {
            // if a product has variants, it's a variable product.
            return 'variable';
        }

        return 'simple';
    }

    /**
     * Converts a remote parent UUID to the local ID.
     *
     * If we cannot find a corresponding local ID, then we cannot convert the product and an exception is thrown.
     * In the future we could consider creating the parent on the fly instead.
     *
     * @param string $remoteParentId
     * @return int
     * @throws MissingProductLocalIdForParentException
     */
    protected function convertRemoteParentUuidToLocalParentId(string $remoteParentId) : int
    {
        $localParentId = $this->productMapRepository->getLocalId($remoteParentId);

        if (empty($localParentId) || ! is_numeric($localParentId)) {
            throw new MissingProductLocalIdForParentException("Failed to retrieve local ID for parent product {$remoteParentId}.");
        }

        return TypeHelper::int($localParentId, 0);
    }

    /**
     * Sets prices in the Product model.
     *
     * @param ProductBase $productBase
     * @param Product $product
     * @return void
     */
    protected function setProductPrices(ProductBase $productBase, Product $product) : void
    {
        if ($productBase->price) {
            $product->setRegularPrice(SimpleMoneyAdapter::getNewInstance()->convertFromSimpleMoney($productBase->price));
        }

        if ($productBase->salePrice) {
            $product->setSalePrice(SimpleMoneyAdapter::getNewInstance()->convertFromSimpleMoney($productBase->salePrice));
        }
    }

    /**
     * Merge the locally supported external IDs with the remote external IDs.
     *
     * This is necessary so that an update does not overwrite the external IDs that are not supported locally.
     *
     * @param Product $product
     * @param ProductBase|null $remoteProduct
     * @return ExternalId[]
     */
    protected function convertExternalIds(Product $product, ?ProductBase $remoteProduct) : array
    {
        $localExternalIds = ExternalIdsAdapter::getNewInstance()->convertToSource($product);

        if ($remoteProduct && $remoteProduct->externalIds) {
            $externalIdsMap = [];

            foreach ($remoteProduct->externalIds as $remoteExternalId) {
                $externalIdsMap[$remoteExternalId->type] = $remoteExternalId;
            }

            foreach ($localExternalIds as $localExternalId) {
                $externalIdsMap[$localExternalId->type] = $localExternalId;
            }

            return array_values($externalIdsMap);
        }

        return $localExternalIds;
    }
}

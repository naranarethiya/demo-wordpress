<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Inventory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VariantListOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Value;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Enums\Products\UpcMetaKeys;

/**
 * Adapter for converting {@see ProductBase} properties into WordPress metadata.
 *
 * This adapter can be used to convert a {@see ProductBase} DTO into an array of key-values that can be used to fill WordPress metadata for a WooCommerce product.
 *
 * @TODO in the future try to decouple the inventory logic MWC-12698 {agibson 2023-06-14}
 *
 * @method static static getNewInstance(ProductBase $productBase)
 */
class ProductPostMetaAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var ProductBase */
    protected ProductBase $source;

    /** @var array<string, array<int, mixed>> array of local metadata - used to compare against the local database */
    protected array $localMeta = [];

    /** @var Summary|null inventory summary related to this product, if available */
    protected ?Summary $inventorySummary = null;

    /**
     * Constructor.
     *
     * @param ProductBase $productBase
     */
    public function __construct(ProductBase $productBase)
    {
        $this->source = $productBase;
    }

    /**
     * Gets the local metadata.
     *
     * @return array<string, array<int, mixed>> key is the metakey; value is an array of values
     */
    public function getLocalMeta() : array
    {
        return $this->localMeta;
    }

    /**
     * Sets the local metadata to use in comparisons.
     *
     * @param array<string, array<int, mixed>> $value
     * @return $this
     */
    public function setLocalMeta(array $value) : ProductPostMetaAdapter
    {
        $this->localMeta = $value;

        return $this;
    }

    /**
     * Gets the inventory summary.
     *
     * @return Summary|null
     */
    public function getInventorySummary() : ?Summary
    {
        return $this->inventorySummary;
    }

    /**
     * Sets the inventory summary for this product.
     *
     * @param Summary|null $inventorySummary
     *
     * @return ProductPostMetaAdapter
     */
    public function setInventorySummary(?Summary $inventorySummary) : ProductPostMetaAdapter
    {
        $this->inventorySummary = $inventorySummary;

        return $this;
    }

    /**
     * Converts specific properties of a {@see ProductBase} DTO into an array of key-values.
     *
     * The output array is intended to fill WordPress metadata for a WooCommerce product.
     *
     * @return array<string, scalar|array<scalar>>
     */
    public function convertFromSource() : array
    {
        $metaData = [
            '_regular_price' => $this->convertAmountFromSource($this->source->price),
            '_sale_price'    => $this->convertAmountFromSource($this->source->salePrice),
            '_price'         => $this->source->salePrice ? $this->convertAmountFromSource($this->source->salePrice) : $this->convertAmountFromSource($this->source->price),
            '_sku'           => $this->source->sku,
            '_tax_class'     => $this->source->taxCategory ?: '',
        ];

        $metaData = $this->convertTypeFromSource($metaData);
        $metaData = $this->convertExternalIdsFromSource($metaData);
        $metaData = $this->convertInventoryFromSource($metaData);
        $metaData = $this->convertFilesFromSource($metaData);
        $metaData = $this->convertMarketplacesDataFromSource($metaData);
        $metaData = $this->convertWeightAndDimensionsFromSource($metaData);
        $metaData = $this->convertOptionsFromSource($metaData);
        $metaData = $this->convertVariantOptionMappingToSource($metaData);

        return $metaData;
    }

    /**
     * Converts specific properties of a {@see ProductBase} DTO into an array of formatted key-values as expected by WPDB.
     *
     * @param $serialize bool whether to serialize any array values or not (default true)
     * @return ($serialize is true ? array<string, array<string>> : array<string, mixed[]>)
     */
    public function convertFromSourceToFormattedArray(bool $serialize = true) : array
    {
        $metaData = [];

        foreach ($this->convertFromSource() as $key => $value) {
            if ($serialize) {
                $metaData[$key] = [is_array($value) ? serialize($value) : (string) $value];
            } else {
                $metaData[$key] = [is_scalar($value) ? (string) $value : $value];
            }
        }

        return $metaData;
    }

    /**
     * Adapts the {@see ProdcutBase} product type into WooCommerce product virtual or downloadable metadata.
     *
     * @param array<string, scalar|array<scalar>> $metaData
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertTypeFromSource(array $metaData) : array
    {
        /* @NOTE in WooCommerce _downloadable and _virtual are separated, but they are one in Commerce, this may result in having a physical downloadable product when adapting back in WooCommerce {unfulvio 2023-04-12} */
        $metaData['_virtual'] = in_array($this->source->type, [ProductBase::TYPE_SERVICE, ProductBase::TYPE_DIGITAL], true) ? 'yes' : 'no';
        // if the product has files we override the flag to be downloadable regardless of the product base type
        $metaData['_downloadable'] = ! empty($this->source->files) ? 'yes' : 'no';

        return $metaData;
    }

    /**
     * Adapts source {@see Inventory} data from {@see ProductBase} into WooCommerce product stock metadata.
     *
     * @param array<string, scalar|array<scalar>> $metaData
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertInventoryFromSource(array $metaData) : array
    {
        if (! $this->source->inventory) {
            return $metaData;
        }

        $isManagingStock = $this->source->inventory->tracking;

        $metaData['_manage_stock'] = $isManagingStock ? 'yes' : 'no';

        if ($isManagingStock && $this->inventorySummary && $this->shouldReadInventoryStock()) {
            $metaData['_stock'] = $this->inventorySummary->totalOnHand;

            /*
             * We need to update the stock status string, even if using a stock quantity, as WooCommerce still references
             * this in some contexts. Example: {@see \WC_Product::is_in_stock()}
             *
             * @NOTE `totalAvailable` value can go into the negatives, which is why we do a <= here (MWC-12835)
             */
            if (is_numeric($metaData['_stock']) && $metaData['_stock'] <= 0) {
                $metaData['_stock_status'] = $this->inventorySummary->isBackorderable ? 'onbackorder' : 'outofstock';
            } else {
                // When reading stock from inventory summary, its default state is "in stock."
                $metaData['_stock_status'] = 'instock';
            }
        }

        return $metaData;
    }

    /**
     * Determines whether inventory stock should be read.
     *
     * @return bool
     */
    protected function shouldReadInventoryStock() : bool
    {
        return InventoryIntegration::shouldLoad() && InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Adapts {@see ProductBase} file data into an array of WooCommerce product metadata.
     *
     * @param array<string, scalar|array<scalar>> $metadata
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertFilesFromSource(array $metadata) : array
    {
        $downloadables = [];

        if ($this->source->files) {
            foreach ($this->source->files as $file) {
                $downloadables[$file->objectKey] = [
                    'id'      => $file->objectKey,
                    'name'    => $file->name ?: '',
                    'file'    => $file->url ?: '',
                    'enabled' => true,
                ];
            }
        }

        if (! empty($downloadables)) {
            $metadata['_downloadable_files'] = $downloadables;
        }

        /* @phpstan-ignore-next-line it returns <string, array<string, scalar> which is still good for us */
        return $metadata;
    }

    /**
     * Adapts the weight and dimensions of a {@see ProductBase} into an array of key-values as WooCommerce metadata.
     *
     * @param array<string, scalar|array<scalar>> $metadata
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertWeightAndDimensionsFromSource(array $metadata) : array
    {
        if (! $this->source->shippingWeightAndDimensions) {
            return $metadata;
        }

        $metadata['_height'] = (string) $this->source->shippingWeightAndDimensions->dimensions->height;
        $metadata['_width'] = (string) $this->source->shippingWeightAndDimensions->dimensions->width;
        $metadata['_length'] = (string) $this->source->shippingWeightAndDimensions->dimensions->length;
        $metadata['_weight'] = (string) $this->source->shippingWeightAndDimensions->weight->value;

        return $metadata;
    }

    /**
     * Adapts {@see ProductBase} properties intended for filling a WooCommerce product's marketplaces metadata.
     *
     * @param array<string, scalar|array<scalar>> $metaData
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertMarketplacesDataFromSource(array $metaData) : array
    {
        switch ($this->source->condition) {
            case ProductBase::CONDITION_NEW:
                $metaData[ProductAdapter::MARKETPLACES_CONDITION_META_KEY] = 'new';
                break;
            case ProductBase::CONDITION_RECONDITIONED:
            case ProductBase::CONDITION_REFURBISHED:
                // @NOTE this is intentional as the Marketplaces feature does not include for now a "reconditioned" condition {unfulvio 2023-04-13}
                $metaData[ProductAdapter::MARKETPLACES_CONDITION_META_KEY] = 'refurbished';
                break;
            case ProductBase::CONDITION_USED:
                $metaData[ProductAdapter::MARKETPLACES_CONDITION_META_KEY] = 'used';
                break;
            default:
                $metaData[ProductAdapter::MARKETPLACES_CONDITION_META_KEY] = '';
                break;
        }

        if ($this->source->brand) {
            $metaData[ProductAdapter::MARKETPLACES_BRAND_META_KEY] = $this->source->brand;
        }

        return $metaData;
    }

    /**
     * Adapts external ID properties from {@see ProductBase} as WooCommerce metadata.
     *
     * @param array<string, scalar|array<scalar>> $metaData
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertExternalIdsFromSource(array $metaData) : array
    {
        if ($this->source->externalIds) {
            foreach ($this->source->externalIds as $externalId) {
                if (ExternalId::TYPE_GTIN === $externalId->type) {
                    $metaData[ProductAdapter::MARKETPLACES_GTIN_META_KEY] = $externalId->value;
                } elseif (ExternalId::TYPE_MPN === $externalId->type) {
                    $metaData[ProductAdapter::MARKETPLACES_MPN_META_KEY] = $externalId->value;
                } elseif (ExternalId::TYPE_UPC === strtolower($externalId->type)) {
                    $metaData = array_merge($metaData, $this->getUpcMetaData($externalId));
                }
            }
        }

        return $metaData;
    }

    /**
     * Builds the product UPC meta data.
     *
     * @param ExternalId $externalId
     * @return array<string, string>
     */
    protected function getUpcMetaData(ExternalId $externalId) : array
    {
        $metaData = [];

        foreach (UpcMetaKeys::cases() as $metaKey) {
            $metaData[$metaKey] = $externalId->value;
        }

        return $metaData;
    }

    /**
     * Converts {@see VariantListOption} data from {@see ProductBase} into WooCommerce product attributes metadata.
     *
     * @param array<string, scalar|array<scalar>> $metaData
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertOptionsFromSource(array $metaData) : array
    {
        $attributes = [];
        $i = 0;

        // variations in WooCommerce do not have a `_product_attributes` meta
        if (! empty($this->source->options) && empty($this->source->parentId)) {
            /** @var VariantListOption $attribute */
            foreach ($this->source->options as $attribute) {
                if (! in_array($attribute->type, [AbstractOption::TYPE_VARIANT_LIST, AbstractOption::TYPE_LIST], true)) {
                    continue;
                }

                $isTaxonomy = $this->isTaxonomyAttribute($attribute->name);
                $attributeName = strtolower($attribute->name);

                $attributes[$attributeName] = [
                    'name'         => $isTaxonomy ? $attribute->name : $attribute->presentation,
                    'position'     => $i,
                    'is_visible'   => 1, // currently we don't have this meta value from Commerce, so we must assume visible
                    'is_variation' => (int) (AbstractOption::TYPE_VARIANT_LIST === $attribute->type),
                    'is_taxonomy'  => (int) $isTaxonomy,
                ];

                if ($isTaxonomy) {
                    // for taxonomy attributes values are inferred from attribute taxonomy terms assigned to the product
                    $attributes[$attributeName]['value'] = '';
                } else {
                    // custom attributes are stored as a pipe-separated list of values
                    $values = [];

                    foreach ($attribute->values as $value) {
                        $values[] = $value->presentation;
                    }

                    $values = array_unique($values);

                    // spaces between pipes are intentional
                    $attributes[$attributeName]['value'] = implode(' | ', $values);
                }

                $i++;
            }
        }

        // wipes the attributes meta if no attributes were found
        $metaData['_product_attributes'] = ! empty($attributes) ? $attributes : [];

        /* @phpstan-ignore-next-line the type of array returned still contains scalar values as intended */
        return $metaData;
    }

    /**
     * Determines whether the supplied attribute `name` relates to a taxonomy in WooCommerce. Attribute taxonomies
     * are always prefixed with `pa_`.
     *
     * @param string $attributeName
     * @return bool
     */
    protected function isTaxonomyAttribute(string $attributeName) : bool
    {
        return strpos($attributeName, 'pa_') === 0;
    }

    /**
     * Converts {@see ProductBase} variant option mapping into WooCommerce product variation attributes metadata.
     *
     * @param array<string, scalar|array<scalar>> $metaData
     * @return array<string, scalar|array<scalar>>
     */
    protected function convertVariantOptionMappingToSource(array $metaData) : array
    {
        // parent variable products don't have direct attribute meta keys
        if (empty($this->source->variantOptionMapping) || empty($this->source->parentId)) {
            return $metaData;
        }

        foreach ($this->source->variantOptionMapping as $attribute) {
            // for taxonomy attributes values are inferred from attribute taxonomy terms assigned to the product
            if ($this->isTaxonomyAttribute($attribute->name)) {
                continue;
            }

            // a taxonomy attribute key will be for example `attribute_pa_color`, while a custom attribute key will be for example `attribute_fabric`
            $metaData['attribute_'.strtolower($attribute->name)] = $this->getVariantMappingAttributeDisplayValue($attribute->name, $attribute->value);
        }

        return $metaData;
    }

    /**
     * Gets the display ("presentation") value for the provided option name and value name (slug) combination.
     *
     * As an example, this might convert:
     * $optionName = `color`
     * $valueName = `blue` (slug/"value" version)
     * to the end result: `Blue` (presentation version)
     *
     * @param string $optionName
     * @param string $valueName
     * @return string
     */
    protected function getVariantMappingAttributeDisplayValue(string $optionName, string $valueName) : string
    {
        if ($displayValue = $this->getVariantMappingAttributeDisplayValueFromOptionsList($optionName, $valueName)) {
            return $displayValue;
        }

        // if we can't find a corresponding "presentation" value, we can attempt to guess it by converting the slug version to uppercase.
        return ucwords($valueName);
    }

    /**
     * Gets the display ("presentation") value that matches the provided option name + value name. We do this by finding
     * a matching record in the `options` array and using that presentation value.
     *
     * For example, `variantOptionMapping` might look like this:
     *
        "variantOptionMapping": [
            {
                "name": "color",
                "value": "blue"
            }
        ]
     *
     * And `options` looks like this:
     *
        "options": [
            {
                "type": "VARIANT_LIST",
                "name": "color",
                "presentation": "Color",
                "cardinality": "1",
                "values": [
                    {
                        "name": "blue",
                        "presentation": "Blue"
                    }
                ]
            }
        ]
     *
     * Given what we know about `variantOptionMapping`, we find the corresponding record in `options` so that we
     * can pull out the `presentation` value.
     *
     * @param string $optionName
     * @param string $valueName
     * @return string|null
     */
    protected function getVariantMappingAttributeDisplayValueFromOptionsList(string $optionName, string $valueName) : ?string
    {
        if (empty($this->source->options)) {
            return null;
        }

        // find the option that matches the provided `$optionName`
        /** @var VariantListOption[] $options */
        $options = ArrayHelper::where($this->source->options, function ($option) use ($optionName) {
            /* @var AbstractOption $option */
            return $option instanceof VariantListOption && $optionName === $option->name;
        }, false);

        if (empty($options[0]) || empty($options[0]->values)) {
            return null;
        }

        // find the value _presentation_ that matches the provided `$valueName`
        /** @var Value[] $optionValues */
        $optionValues = ArrayHelper::where($options[0]->values, function ($value) use ($valueName) {
            /* @var Value $value */
            return $valueName === $value->name;
        }, false);

        if (empty($optionValues[0])) {
            return null;
        }

        return $optionValues[0]->presentation;
    }

    /**
     * Converts a monetary amount from {@see SimpleMoney} to a numerical string intended as WooCommerce metadata.
     *
     * @param SimpleMoney|null $amount
     * @return string numerical value of the amount
     */
    protected function convertAmountFromSource(?SimpleMoney $amount) : string
    {
        if (! $amount) {
            return '';
        }

        return (string) CurrencyAmountAdapter::getNewInstance($amount->value, $amount->currencyCode)
            ->convertToSource(
                CurrencyAmount::getNewInstance()
                    ->setAmount($amount->value)
                    ->setCurrencyCode($amount->currencyCode)
            );
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // no-op
    }
}

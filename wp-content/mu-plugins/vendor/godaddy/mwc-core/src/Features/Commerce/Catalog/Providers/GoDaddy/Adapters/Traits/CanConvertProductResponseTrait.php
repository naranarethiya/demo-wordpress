<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Dimensions;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\File;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ImageAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Inventory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ListOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ManufacturerData;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ShippingWeightAndDimensions;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\UnsupportedAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VariantListOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VariantOptionMapping;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VideoAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Weight;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ExternalIdsAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Value;

/**
 * Trait used by adapters that can convert a Commerce product response to a {@see ProductBase} object.
 */
trait CanConvertProductResponseTrait
{
    use CanParseNullableStringPropertyTrait;
    use CanConvertDateTimeFromTimestampTrait;

    /**
     * Converts a Commerce product response to a {@see ProductBase} object.
     *
     * @param array<string, mixed> $responseData
     * @return ProductBase
     * @throws MissingProductRemoteIdException
     */
    protected function convertProductResponse(array $responseData) : ProductBase
    {
        return new ProductBase([
            'active'                      => TypeHelper::bool(ArrayHelper::get($responseData, 'active', false), false),
            'allowCustomPrice'            => TypeHelper::bool(ArrayHelper::get($responseData, 'allowCustomPrice', false), false),
            'altId'                       => $this->parseNullableStringProperty($responseData, 'altId'),
            'assets'                      => $this->convertAssets($responseData),
            'brand'                       => $this->parseNullableStringProperty($responseData, 'brand'),
            'categoryIds'                 => TypeHelper::arrayOfStrings(ArrayHelper::get($responseData, 'categoryIds')),
            'channelIds'                  => TypeHelper::arrayOfStrings(ArrayHelper::get($responseData, 'channelIds', [])),
            'condition'                   => $this->parseNullableStringProperty($responseData, 'condition'),
            'createdAt'                   => $this->convertDateTimeFromTimestamp($responseData, 'createdAt'),
            'deletedAt'                   => $this->convertDateTimeFromTimestamp($responseData, 'deletedAt'),
            'description'                 => $this->parseNullableStringProperty($responseData, 'description'),
            'ean'                         => $this->parseNullableStringProperty($responseData, 'ean'),
            'externalIds'                 => $this->convertExternalIds($responseData),
            'files'                       => $this->convertFiles($responseData),
            'inventory'                   => $this->convertInventory($responseData),
            'manufacturerData'            => $this->convertManufacturerData($responseData),
            'name'                        => TypeHelper::string(ArrayHelper::get($responseData, 'name'), ''),
            'options'                     => $this->convertOptions($responseData),
            'parentId'                    => $this->parseNullableStringProperty($responseData, 'parentId'),
            'price'                       => $this->convertPriceToSimpleMoney($responseData, 'price'),
            'productId'                   => $this->validProductId($responseData, 'productId'),
            'salePrice'                   => $this->convertPriceToSimpleMoney($responseData, 'salePrice'),
            'shippingWeightAndDimensions' => $this->convertShippingWeightAndDimensions($responseData),
            'shortCode'                   => $this->parseNullableStringProperty($responseData, 'shortCode'),
            'sku'                         => TypeHelper::string(ArrayHelper::get($responseData, 'sku'), ''),
            'taxCategory'                 => $this->parseNullableStringProperty($responseData, 'taxCategory'),
            'type'                        => $this->parseNullableStringProperty($responseData, 'type'),
            'updatedAt'                   => $this->convertDateTimeFromTimestamp($responseData, 'updatedAt'),
            'variantOptionMapping'        => $this->convertVariantOptionMappingToSource($responseData),
            'variants'                    => $this->parseNullableArrayOfStringsProperty($responseData, 'variants'),
        ]);
    }

    /**
     * Convert the variant option mapping.
     *
     * @param array<mixed> $responseData
     * @return ?VariantOptionMapping[]
     */
    protected function convertVariantOptionMappingToSource(array $responseData) : ?array
    {
        /** @var array<array<string, string>> $mapping */
        $mapping = TypeHelper::array(ArrayHelper::get($responseData, 'variantOptionMapping', []), []);

        if (empty($mapping)) {
            return null;
        }

        $finalMapping = [];
        foreach ($mapping as $optionMap) {
            if (ArrayHelper::accessible($optionMap) && ! empty($optionMap['name']) && ! empty($optionMap['value'])) {
                $finalMapping[] = VariantOptionMapping::getNewInstance([
                    'name'  => $optionMap['name'],
                    'value' => $optionMap['value'],
                ]);
            }
        }

        return $finalMapping ?: null;
    }

    /**
     * Parses a simple nullable property from a Commerce response to array of strings or null.
     *
     * @param array<string, mixed> $responseData
     * @param string $key
     * @return string[]|null
     */
    protected function parseNullableArrayOfStringsProperty(array $responseData, string $key) : ?array
    {
        $value = ArrayHelper::get($responseData, $key);

        if (null === $value) {
            return null;
        }

        return TypeHelper::arrayOfStrings($value, false);
    }

    /**
     * Convert product assets from a Commerce response into corresponding {@see AbstractAsset} objects.
     *
     * @param array<string, mixed> $responseData
     * @return ?array<AbstractAsset|ImageAsset|VideoAsset|UnsupportedAsset>
     */
    protected function convertAssets(array $responseData) : ?array
    {
        $responseAssets = TypeHelper::array(ArrayHelper::get($responseData, 'assets'), []);

        if (empty($responseAssets)) {
            return null;
        }

        $assets = [];

        /** @var array<string, mixed> $responseAsset */
        foreach ($responseAssets as $responseAsset) {
            if (! is_array($responseAsset)) {
                continue;
            }

            $type = TypeHelper::string(ArrayHelper::get($responseAsset, 'type'), '');
            $contentType = $this->parseNullableStringProperty($responseAsset, 'contentType');
            $name = TypeHelper::string(ArrayHelper::get($responseAsset, 'name'), '');
            $thumbnail = TypeHelper::string(ArrayHelper::get($responseAsset, 'thumbnail'), '');
            $url = TypeHelper::string(ArrayHelper::get($responseAsset, 'url'), '');

            $assetArgs = [
                'contentType' => $contentType,
                'name'        => $name,
                'thumbnail'   => $thumbnail,
                'url'         => $url,
            ];

            switch ($type) {
                case AbstractAsset::TYPE_IMAGE:
                    $asset = ImageAsset::getNewInstance($assetArgs);
                    break;
                case AbstractAsset::TYPE_VIDEO:
                    $asset = VideoAsset::getNewInstance($assetArgs);
                    break;
                default:
                    $asset = UnsupportedAsset::getNewInstance(array_merge($assetArgs, ['type' => $type]));
                    break;
            }

            $assets[] = $asset;
        }

        return $assets;
    }

    /**
     * Converts external IDs from Commerce response data into an array of {@see ExternalId} objects.
     *
     * @param array<string, mixed> $responseData
     * @return ?ExternalId[]
     */
    protected function convertExternalIds(array $responseData) : ?array
    {
        /** @var array<array<string, string>> $externalIdsData */
        $externalIdsData = TypeHelper::array(ArrayHelper::get($responseData, 'externalIds'), []);

        return ExternalIdsAdapter::getNewInstance()->convertToSourceFromArray($externalIdsData);
    }

    /**
     * Converts files from Commerce response data into an array of {@see File} objects.
     *
     * @param array<string, mixed> $responseData
     * @return ?File[]
     */
    protected function convertFiles(array $responseData) : ?array
    {
        $filesData = TypeHelper::array(ArrayHelper::get($responseData, 'files'), []);

        if (empty($filesData)) {
            return null;
        }

        $files = [];

        /** @var array<string, mixed> $fileData */
        foreach ($filesData as $fileData) {
            if (! is_array($fileData)) {
                continue;
            }

            $description = $this->parseNullableStringProperty($fileData, 'description');
            $name = TypeHelper::string(ArrayHelper::get($fileData, 'name'), '');
            $objectKey = TypeHelper::string(ArrayHelper::get($fileData, 'objectKey'), '');
            $size = is_numeric(ArrayHelper::get($fileData, 'size')) ? TypeHelper::int(ArrayHelper::get($fileData, 'size'), 0) : null;
            $type = $this->parseNullableStringProperty($fileData, 'type');
            $url = $this->parseNullableStringProperty($fileData, 'url');

            $files[] = File::getNewInstance([
                'description' => $description,
                'name'        => $name,
                'objectKey'   => $objectKey,
                'size'        => $size,
                'type'        => $type,
                'url'         => $url,
            ]);
        }

        return $files;
    }

    /**
     * Converts inventory information from Commerce response data into an {@see Inventory} object.
     *
     * @param array<string, mixed> $responseData
     * @return ?Inventory
     */
    protected function convertInventory(array $responseData) : ?Inventory
    {
        $inventoryData = TypeHelper::array(ArrayHelper::get($responseData, 'inventory'), []);

        if (empty($inventoryData)) {
            return null;
        }

        $externalService = TypeHelper::bool(ArrayHelper::get($inventoryData, 'externalService'), false);
        $tracking = TypeHelper::bool(ArrayHelper::get($inventoryData, 'tracking'), false);

        return Inventory::getNewInstance([
            'externalService' => $externalService,
            'tracking'        => $tracking,
        ]);
    }

    /**
     * Converts manufacturer data from Commerce response data into a {@see ManufacturerData} object.
     *
     * @param array<string, mixed> $responseData
     * @return ?ManufacturerData
     */
    protected function convertManufacturerData(array $responseData) : ?ManufacturerData
    {
        $manufacturerData = TypeHelper::array(ArrayHelper::get($responseData, 'manufacturerData'), []);

        if (empty($manufacturerData)) {
            return null;
        }

        $minimumAdvertisedPrice = $this->convertPriceToSimpleMoney($manufacturerData, 'minimumAdvertisedPrice');
        $suggestedRetailPrice = $this->convertPriceToSimpleMoney($manufacturerData, 'suggestedRetailPrice');
        $modelNumber = $this->parseNullableStringProperty($manufacturerData, 'modelNumber');
        $name = $this->parseNullableStringProperty($manufacturerData, 'name');
        $warrantyPeriod = $this->parseNullableStringProperty($manufacturerData, 'warrantyPeriod');

        return ManufacturerData::getNewInstance([
            'minimumAdvertisedPrice' => $minimumAdvertisedPrice,
            'suggestedRetailPrice'   => $suggestedRetailPrice,
            'modelNumber'            => $modelNumber,
            'name'                   => $name,
            'warrantyPeriod'         => $warrantyPeriod,
        ]);
    }

    /**
     * Converts options from Commerce response data to a corresponding {@see AbstractOption} object.
     *
     * @param array<string, mixed> $responseData
     * @return ?AbstractOption[]
     */
    protected function convertOptions(array $responseData) : ?array
    {
        $optionsData = TypeHelper::array(ArrayHelper::get($responseData, 'options'), []);

        if (empty($optionsData)) {
            return null;
        }

        $options = [];

        /** @var array<string, mixed> $optionData */
        foreach ($optionsData as $optionData) {
            if (! is_array($optionData)) {
                continue;
            }

            $type = $this->parseNullableStringProperty($optionData, 'type');
            $name = $this->parseNullableStringProperty($optionData, 'name');
            $presentation = $this->parseNullableStringProperty($optionData, 'presentation');
            $cardinality = $this->parseNullableStringProperty($optionData, 'cardinality');
            $values = $this->convertOptionValues(TypeHelper::array(ArrayHelper::get($optionData, 'values'), []));

            if (! $name || ! $presentation) {
                continue;
            }

            $optionArgs = [
                'name'         => $name,
                'presentation' => $presentation,
                'values'       => $values,
            ];

            if (! empty($cardinality)) {
                $optionArgs['cardinality'] = $cardinality;
            }

            switch ($type) {
                case AbstractOption::TYPE_LIST:
                    $option = ListOption::getNewInstance($optionArgs);
                    break;
                case AbstractOption::TYPE_VARIANT_LIST:
                    $option = VariantListOption::getNewInstance($optionArgs);
                    break;
                default:
                    continue 2;
            }

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Converts option values from Commerce response data to an array of {@see Value} objects.
     *
     * @param array<string, mixed> $optionValuesData
     * @return Value[]
     */
    protected function convertOptionValues(array $optionValuesData) : array
    {
        $values = [];

        /** @var array<string, mixed> $optionValueData */
        foreach ($optionValuesData as $optionValueData) {
            if (! is_array($optionValueData)) {
                continue;
            }

            $name = $this->parseNullableStringProperty($optionValueData, 'name');
            $presentation = $this->parseNullableStringProperty($optionValueData, 'presentation');

            if (! $name || ! $presentation) {
                continue;
            }

            $values[] = Value::getNewInstance([
                'name'         => $name,
                'presentation' => $presentation,
            ]);
        }

        return $values;
    }

    /**
     * Converts the product price from Commerce response data to a {@see SimpleMoney} object.
     *
     * @param array<string, mixed> $responseData
     * @param string $key
     * @return SimpleMoney|null
     */
    protected function convertPriceToSimpleMoney(array $responseData, string $key) : ?SimpleMoney
    {
        $value = TypeHelper::array(ArrayHelper::get($responseData, $key), []);

        if (! $value) {
            // sale prices are expected to be nullable
            return null;
        }

        $currencyCode = ArrayHelper::get($value, 'currencyCode');
        $priceValue = ArrayHelper::get($value, 'value');

        if (! $currencyCode) {
            new SentryException(sprintf('Invalid %s data: missing currency code', $key));

            return null;
        }

        if (! is_int($priceValue)) {
            new SentryException(sprintf('Invalid %s data: missing price value', $key));

            return null;
        }

        return SimpleMoney::getNewInstance([
            'currencyCode' => TypeHelper::string($currencyCode, ''),
            'value'        => TypeHelper::int($priceValue, 0),
        ]);
    }

    /**
     * Converts {@see ShippingWeightAndDimensions} from the Commerce response data.
     *
     * @param array<string, mixed> $responseData
     * @return ?ShippingWeightAndDimensions
     */
    protected function convertShippingWeightAndDimensions(array $responseData) : ?ShippingWeightAndDimensions
    {
        $shippingWeightAndDimensionsData = TypeHelper::array(ArrayHelper::get($responseData, 'shippingWeightAndDimensions'), []);

        if (empty($shippingWeightAndDimensionsData)) {
            return null;
        }

        $dimensionsData = TypeHelper::array(ArrayHelper::get($shippingWeightAndDimensionsData, 'dimensions'), []);
        $height = TypeHelper::float(ArrayHelper::get($dimensionsData, 'height'), 0.0);
        $length = TypeHelper::float(ArrayHelper::get($dimensionsData, 'length'), 0.0);
        $width = TypeHelper::float(ArrayHelper::get($dimensionsData, 'width'), 0.0);
        $unit = TypeHelper::string(ArrayHelper::get($dimensionsData, 'unit'), '');

        if (! $unit) {
            return null;
        }

        $dimensions = Dimensions::getNewInstance(['height' => $height, 'length' => $length, 'width' => $width, 'unit' => $unit]);

        $weightData = TypeHelper::array(ArrayHelper::get($shippingWeightAndDimensionsData, 'weight'), []);
        $weight = TypeHelper::float(ArrayHelper::get($weightData, 'value'), 0.0);
        $unit = TypeHelper::string(ArrayHelper::get($weightData, 'unit'), '');

        if (! $unit) {
            return null;
        }

        $weight = Weight::getNewInstance(['value' => $weight, 'unit' => $unit]);

        return ShippingWeightAndDimensions::getNewInstance([
            'dimensions' => $dimensions,
            'weight'     => $weight,
        ]);
    }

    /**
     * Returns a valid product ID or throws an exception.
     *
     * @param array<mixed> $responseData
     * @param string $key
     * @return string|null
     * @throws MissingProductRemoteIdException
     */
    protected function validProductId(array $responseData, string $key) : ?string
    {
        $value = TypeHelper::string(ArrayHelper::get($responseData, $key), '');

        if (! $value) {
            throw new MissingProductRemoteIdException('Product ID is missing from the response.');
        }

        return $value;
    }
}

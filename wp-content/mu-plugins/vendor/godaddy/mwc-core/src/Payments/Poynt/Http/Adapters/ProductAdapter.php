<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An adapter for converting the core product object to and from Poynt API data.
 */
class ProductAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var Product */
    protected Product $source;

    /**
     * Constructs the adapter.
     *
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->source = $product;
    }

    /**
     * Converts the source product to Poynt API data.
     *
     * @return array
     */
    public function convertFromSource() : array
    {
        $sku = $this->source->getSku();

        $data = [
            'name'      => $this->source->getName(),
            'shortCode' => $this->formatShortCode($sku),
            'sku'       => $sku,
        ];

        // add the price if available
        if ($regularPrice = $this->source->getRegularPrice()) {
            $data['price'] = [
                'amount'   => $regularPrice->getAmount(),
                'currency' => $regularPrice->getCurrencyCode(),
            ];
        }

        // always add an empty property so it can be patched later
        $data['selectableVariants'] = [];

        if ($variations = $this->convertAttributesFromSource($this->source->getAttributeData())) {
            $data['selectableVariants'] = [
                [
                    'selectableVariations' => $variations,
                    'sku'                  => $data['sku'],
                ],
            ];
        }

        /*
         * Filter the Poynt API data
         *
         * @param array $data Poynt API data
         */
        return (array) apply_filters('mwc_payments_godaddy_payments_synced_product_data', $data);
    }

    /**
     * Formats the given SKU into a shortcode required by the Poynt API.
     *
     * @param string $sku
     * @return string
     */
    protected function formatShortCode(string $sku) : string
    {
        return substr(str_replace(['-', '_'], '', $sku), 0, 5);
    }

    /**
     * Converts the given attributes to Poynt API data.
     *
     * @param array $attributes
     * @return array
     */
    protected function convertAttributesFromSource(array $attributes) : array
    {
        $selectableVariations = [];

        foreach ($attributes as $name => $attribute) {
            if (! is_string($name) || '' === $name || ! ArrayHelper::accessible($attribute)) {
                continue;
            }

            $selectableVariations[] = [
                'attribute'   => $name,
                'cardinality' => ArrayHelper::get($attribute, 'cardinality', 1),
                'values'      => array_map(function ($option) {
                    return [
                        'name' => $option,
                    ];
                }, ArrayHelper::get($attribute, 'options', [])),
            ];
        }

        return $selectableVariations;
    }

    /**
     * Applies Poynt API data to the source product object.
     *
     * @param array|null $data
     *
     * @return Product
     */
    public function convertToSource(array $data = []) : Product
    {
        if ($name = TypeHelper::string(ArrayHelper::get($data, 'name'), '')) {
            $this->source->setName($name);
        }

        $amount = ArrayHelper::get($data, 'price.amount');
        $currency = ArrayHelper::get($data, 'price.currency');

        if ($amount && $currency) {
            $this->source->setRegularPrice((new CurrencyAmount())
                ->setAmount($amount)
                ->setCurrencyCode($currency)
            );
        }

        if ($type = $this->convertToType(ArrayHelper::get($data, 'type', ''))) {
            $this->source->setType($type);
        }

        if ($status = $this->convertToStatus(ArrayHelper::get($data, 'status', ''))) {
            $this->source->setStatus($status);
        }

        if ($sku = TypeHelper::string(ArrayHelper::get($data, 'sku'), '')) {
            $this->source->setSku($sku);
        }

        if ($remoteId = TypeHelper::string(ArrayHelper::get($data, 'id'), '')) {
            $this->source->setRemoteId($remoteId);
        }

        if ($remoteParentId = TypeHelper::string(ArrayHelper::get($data, 'businessId'), '')) {
            $this->source->setRemoteParentId($remoteParentId);
        }

        if ($variants = ArrayHelper::get($data, 'selectableVariants')) {
            $this->source->setAttributeData(
                $this->convertVariantsToSource($variants, ArrayHelper::get($data, 'isCustomPrice', false))
            );
        }

        return $this->source;
    }

    /**
     * Converts the given Poynt API variants into product attributes.
     *
     * @param array $variants
     * @return array
     */
    protected function convertVariantsToSource(array $variants, bool $isCustomPrice = false) : array
    {
        $attributes = [];

        foreach ($variants as $variant) {
            foreach (ArrayHelper::get($variant, 'selectableVariations', []) as $variation) {
                if (! $name = ArrayHelper::get($variation, 'attribute')) {
                    continue;
                }

                $attributes[$name] = [
                    'isCustomPrice' => $isCustomPrice,
                    'cardinality'   => ArrayHelper::get($variation, 'cardinality', 1),
                    'options'       => array_filter(array_map(function ($value) {
                        return ArrayHelper::get($value, 'name');
                    }, ArrayHelper::get($variation, 'values', []))),
                ];
            }
        }

        return $attributes;
    }

    /**
     * Converts the given Poynt product type to a core product type.
     *
     * Currently, SIMPLE products are the only type we handle.
     *
     * @param string $type
     * @return string|null
     */
    protected function convertToType(string $type)
    {
        return 'SIMPLE' === strtoupper($type) ? 'simple' : null;
    }

    /**
     * Converts the given Poynt product status to a core product status.
     *
     * Currently, only ACTIVE products are given a core status.
     *
     * @param string $status
     * @return string|null
     */
    protected function convertToStatus(string $status)
    {
        return 'ACTIVE' === strtoupper($status) ? 'publish' : null;
    }
}

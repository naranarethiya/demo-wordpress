<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Dimensions as ProductDimensions;
use GoDaddy\WordPress\MWC\Common\Models\Weight as ProductWeight;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Dimensions as ProductBaseDimensions;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ShippingWeightAndDimensions;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Weight as ProductBaseWeight;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter to convert a {@see Product} model dimensions and weight into a {@see ShippingWeightAndDimensions} DTO.
 */
class ShippingWeightAndDimensionsAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a product's shipping weight and dimensions into a {@see ShippingWeightAndDimensions} DTO.
     *
     * @param Product|null $product
     * @return ShippingWeightAndDimensions|null
     */
    public function convertToSource(Product $product = null) : ?ShippingWeightAndDimensions
    {
        if (! $product || ! $this->shouldConvertProductDimensionsAndWeightToSource($product) || ! $product->getWeight()) {
            return null;
        }

        return ShippingWeightAndDimensions::getNewInstance([
            'dimensions' => $this->convertProductDimensionsToSource($product->getDimensions()),
            'weight'     => $this->convertProductWeightToSource($product->getWeight()),
        ]);
    }

    /**
     * Determines whether the product's dimensions and weight should be converted.
     *
     * @param Product $product
     * @return bool
     */
    protected function shouldConvertProductDimensionsAndWeightToSource(Product $product) : bool
    {
        $dimensions = $product->getDimensions();

        return $product->getWeight() && $dimensions->getHeight() && $dimensions->getLength() && $dimensions->getWidth();
    }

    /**
     * Converts the product's {@see ProductDimensions} into a {@see ProductBaseDimensions} DTO.
     *
     * @param ProductDimensions $dimensions
     * @return ProductBaseDimensions
     */
    protected function convertProductDimensionsToSource(ProductDimensions $dimensions) : ProductBaseDimensions
    {
        return ProductBaseDimensions::getNewInstance([
            'height' => $dimensions->getHeight(),
            'length' => $dimensions->getLength(),
            'width'  => $dimensions->getWidth(),
            'unit'   => $dimensions->getUnitOfMeasurement(),
        ]);
    }

    /**
     * Converts the product's {@see ProductWeight} into a {@see ProductBaseWeight} DTO.
     *
     * @param ProductWeight $weight
     * @return ProductBaseWeight
     */
    protected function convertProductWeightToSource(ProductWeight $weight) : ProductBaseWeight
    {
        return ProductBaseWeight::getNewInstance([
            'value' => $weight->getValue(),
            'unit'  => $weight->getUnitOfMeasurement(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource()
    {
        // no-op for now
    }
}

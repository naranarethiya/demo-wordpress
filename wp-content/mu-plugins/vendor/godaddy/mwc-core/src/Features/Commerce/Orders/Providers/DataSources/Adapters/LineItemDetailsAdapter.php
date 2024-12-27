<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\AttributeValue;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemDetails;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\Traits\CanGetProductFromLineItemTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapts a LineItem object to, and from a LineItemDetails object.
 */
class LineItemDetailsAdapter implements DataObjectAdapterContract
{
    use CanGetProductFromLineItemTrait;

    protected ?Product $product = null;

    /**
     * Sets the product associated with the target {@see LineItem}.
     *
     * @param Product|null $product
     * @return $this
     */
    public function setProduct(?Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @param ?LineItemDetails $source
     * @param ?LineItem $lineItem
     *
     * @return LineItem
     *
     * {@inheritDoc}
     */
    public function convertFromSource($source, ?LineItem $lineItem = null) : LineItem
    {
        $lineItem ??= new LineItem();

        if (! $source || ! $source->sku) {
            return $lineItem;
        }

        return $lineItem->setSku($source->sku);
    }

    /**
     * @param LineItem $target
     *
     * @return LineItemDetails
     *
     * {@inheritDoc}
     */
    public function convertToSource($target) : LineItemDetails
    {
        return new LineItemDetails([
            'sku'             => $target->getSku() ?: null,
            'selectedOptions' => $this->getSelectedOptions($target),
        ]);
    }

    /**
     * Gets the selected options for a line item.
     *
     * Determines the variant attribute mapping from the product associated with the line item.
     * Should return null if the array is empty.
     *
     * @param LineItem $item
     * @return LineItemOption[]
     */
    protected function getSelectedOptions(LineItem $item) : array
    {
        $this->product = $this->product ?? $this->getProductFromLineItem($item);

        if (! $this->product) {
            return [];
        }

        return $this->convertAttributeMappingIntoSelectedOptions((array) $this->product->getVariantAttributeMapping());
    }

    /**
     * Converts an array of attributes and values that represent a product variant into an array of line item options.
     *
     * @param array<AttributeValue|null> $values
     * @return LineItemOption[]
     */
    protected function convertAttributeMappingIntoSelectedOptions(array $values) : array
    {
        return array_map(static function (AttributeValue $value) {
            return new LineItemOption([
                'attribute' => $value->getAttribute()->getLabel(),
                'values'    => [$value->getLabel()],
            ]);
        }, array_values(array_filter($values)));
    }
}

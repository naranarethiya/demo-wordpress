<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\AttributeValue;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Value;

/**
 * Adapter for converting WooCommerce attributes and {@see AbstractOption} DTOs.
 */
class ValueAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a source {@see AttributeValue} into a native {@see Value}.
     *
     * @param AttributeValue|null $attributeValue
     * @return Value|null
     */
    public function convertToSource(?AttributeValue $attributeValue = null) : ?Value
    {
        if (! $attributeValue) {
            return null;
        }

        return Value::getNewInstance([
            'name'         => $attributeValue->getName(),
            'presentation' => $attributeValue->getLabel(),
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

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ListOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VariantListOption;

/**
 * Adapter for converting WooCommerce attributes and {@see AbstractOption} DTOs.
 *
 * @see ListOption
 * @see VariantListOption
 */
class OptionAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a source {@see Attribute} into a native {@see AbstractOption}.
     *
     * Disable the PHP Compatibility rule for now because it doesn't take into account use statements: https://github.com/PHPCompatibility/PHPCompatibility/issues/1291
     * @phpcs:disable PHPCompatibility.Classes.NewClasses.attributeFound
     *
     * @param Attribute|null $attribute
     * @return AbstractOption|null
     */
    public function convertToSource(?Attribute $attribute = null) : ?AbstractOption
    {
        // @phpcs:enable PHPCompatibility.Classes.NewClasses.attributeFound

        if (! $attribute) {
            return null;
        }

        $values = [];

        foreach ($attribute->getValues() as $value) {
            if ($value = ValueAdapter::getNewInstance()->convertToSource($value)) {
                $values[] = $value;
            }
        }

        $args = [
            'name'         => $attribute->getName(),
            'presentation' => $attribute->getLabel(),
            'values'       => $values,
        ];

        return $attribute->isForVariant()
            ? VariantListOption::getNewInstance($args)
            : ListOption::getNewInstance($args);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource()
    {
        // no-op for now
    }
}

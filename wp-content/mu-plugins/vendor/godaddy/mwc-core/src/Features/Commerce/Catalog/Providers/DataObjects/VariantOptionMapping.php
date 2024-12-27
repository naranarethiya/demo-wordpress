<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Describes a {@see VariantListOption} that correspond a product variant.
 *
 * @method static static getNewInstance(array $data)
 */
class VariantOptionMapping extends AbstractDataObject
{
    /** @var string identifies the name of the associated {@see VariantListOption} */
    public string $name;

    /** @var string the presentational value of the associated {@see VariantListOption} */
    public string $value;

    /**
     * Constructor.
     *
     * @param array{
     *     name: string,
     *     value: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

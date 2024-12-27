<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

/**
 * Identifies product characteristics as declared by the manufacturer.
 */
class ManufacturerData extends AbstractDataObject
{
    /** @var string|null manufacturer name */
    public ?string $name = null;

    /** @var string|null manufacturer model number */
    public ?string $modelNumber = null;

    /** @var SimpleMoney|null */
    public ?SimpleMoney $suggestedRetailPrice = null;

    /** @var SimpleMoney|null */
    public ?SimpleMoney $minimumAdvertisedPrice = null;

    /** @var string|null */
    public ?string $warrantyPeriod = null;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     minimumAdvertisedPrice?: ?SimpleMoney,
     *     modelNumber?: ?string,
     *     name?: ?string,
     *     suggestedRetailPrice?: ?SimpleMoney,
     *     warrantyPeriod?: ?string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

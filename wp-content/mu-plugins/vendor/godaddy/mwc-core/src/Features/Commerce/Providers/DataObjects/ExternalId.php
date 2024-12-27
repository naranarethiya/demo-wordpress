<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

/**
 * A collection of alternate identifiers for the component, such as GTIN or ASIN.
 */
class ExternalId extends AbstractDataObject
{
    /** @var string GTIN type */
    const TYPE_GTIN = 'GTIN';

    /** @var string MPN type */
    const TYPE_MPN = 'MPN';

    /** @var string UPC(EAN) type */
    const TYPE_UPC = 'upc'; // must be lowercase

    /** @var string one of GTIN, MPN or other */
    public string $type;

    /** @var string */
    public string $value;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     type: string,
     *     value: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

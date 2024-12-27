<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class ListLocationsInput extends AbstractDataObject
{
    public string $storeId;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

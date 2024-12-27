<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class BaseLevelInput extends AbstractDataObject
{
    public string $storeId;
    public string $levelId;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     *     levelId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

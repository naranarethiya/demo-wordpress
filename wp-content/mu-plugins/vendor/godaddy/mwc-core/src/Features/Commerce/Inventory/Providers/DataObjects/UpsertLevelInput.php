<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class UpsertLevelInput extends AbstractDataObject
{
    public string $storeId;
    public Level $level;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     *     level: Level,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

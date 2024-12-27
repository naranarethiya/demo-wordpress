<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;

class Location extends AbstractDataObject
{
    public bool $active;
    public ?Address $address = null;
    public ?string $inventoryLocationId = null;
    public int $priority;
    public string $type;

    /**
     * Creates a new location.
     *
     * @param array{
     *     active: bool,
     *     Address?: ?Address,
     *     inventoryLocationId?: ?string,
     *     priority: int,
     *     type: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

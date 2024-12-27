<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;

interface ListLocationsResponseContract
{
    /**
     * Gets the locations.
     *
     * @return Location[]
     */
    public function getLocations() : array;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLocationsResponseContract;

class ListLocationsResponse implements ListLocationsResponseContract
{
    /** @var Location[] */
    protected array $locations;

    /**
     * @param Location[] $locations
     */
    public function __construct(array $locations)
    {
        $this->locations = $locations;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocations() : array
    {
        return $this->locations;
    }
}

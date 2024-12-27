<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLocationResponseContract;

class CreateOrUpdateLocationResponse implements CreateOrUpdateLocationResponseContract
{
    /** @var Location */
    protected Location $location;

    /**
     * @param Location $location
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocation() : Location
    {
        return $this->location;
    }

    /**
     * {@inheritDoc}
     */
    public function setLocation(Location $value) : CreateOrUpdateLocationResponse
    {
        $this->location = $value;

        return $this;
    }
}

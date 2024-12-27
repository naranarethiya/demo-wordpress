<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;

interface LocationResponseContract
{
    /**
     * Gets the Location.
     *
     * @return Location
     */
    public function getLocation() : Location;

    /**
     * Sets the Location.
     *
     * @param Location $value
     *
     * @return $this
     */
    public function setLocation(Location $value) : LocationResponseContract;
}

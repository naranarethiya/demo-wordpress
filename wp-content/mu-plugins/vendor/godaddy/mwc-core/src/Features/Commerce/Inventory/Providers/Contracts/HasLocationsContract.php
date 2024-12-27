<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

interface HasLocationsContract
{
    /**
     * Returns a locations gateway.
     *
     * @return LocationsGatewayContract
     */
    public function locations() : LocationsGatewayContract;
}

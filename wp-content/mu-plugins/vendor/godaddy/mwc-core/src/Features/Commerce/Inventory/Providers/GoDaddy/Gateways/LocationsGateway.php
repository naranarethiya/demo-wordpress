<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\LocationsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLocationsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\CreateLocationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\ListLocationsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\ReadLocationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\UpdateLocationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

class LocationsGateway extends AbstractGateway implements LocationsGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function createOrUpdate(UpsertLocationInput $input) : Location
    {
        $adapterClass = isset($input->location->inventoryLocationId) ? UpdateLocationRequestAdapter::class : CreateLocationRequestAdapter::class;

        /** @var Location $result */
        $result = $this->doAdaptedRequest($adapterClass::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function read(ReadLocationInput $input) : Location
    {
        /** @var Location $result */
        $result = $this->doAdaptedRequest(ReadLocationRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function list(ListLocationsInput $input) : array
    {
        /** @var array<Location> $result */
        $result = $this->doAdaptedRequest(ListLocationsRequestAdapter::getNewInstance($input));

        return $result;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\LevelsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\LocationsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ReservationsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\SummariesGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways\LevelsGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways\LocationsGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways\ReservationsGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways\SummariesGateway;

/**
 * The GoDaddy inventory provider.
 */
class InventoryProvider implements InventoryProviderContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function levels() : LevelsGatewayContract
    {
        return LevelsGateway::getNewInstance();
    }

    /**
     * {@inheritDoc}
     */
    public function reservations() : ReservationsGatewayContract
    {
        return ReservationsGateway::getNewInstance();
    }

    /**
     * {@inheritDoc}
     */
    public function locations() : LocationsGatewayContract
    {
        return LocationsGateway::getNewInstance();
    }

    /**
     * {@inheritDoc}
     */
    public function summaries() : SummariesGatewayContract
    {
        return SummariesGateway::getNewInstance();
    }
}

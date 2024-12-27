<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Inventory;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LocationMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\ReservationMappingService;

class InventoryMappingServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        LevelMappingServiceContract::class,
        LocationMappingServiceContract::class,
        ReservationMappingServiceContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(LevelMappingServiceContract::class, LevelMappingService::class);
        $this->getContainer()->singleton(LocationMappingServiceContract::class, LocationMappingService::class);
        $this->getContainer()->singleton(ReservationMappingServiceContract::class, ReservationMappingService::class);
    }
}

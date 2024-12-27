<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Inventory;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsServiceWithCache;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LocationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\ProductInventoryCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\ReservationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\SummariesService;

class InventoryServicesServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        LevelsServiceContract::class,
        LevelsServiceWithCacheContract::class,
        LocationsServiceContract::class,
        ReservationsServiceContract::class,
        SummariesServiceContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(LevelsServiceContract::class, LevelsService::class);
        $this->getContainer()->singleton(LevelsServiceWithCacheContract::class, LevelsServiceWithCache::class);
        $this->getContainer()->singleton(LocationsServiceContract::class, LocationsService::class);
        $this->getContainer()->singleton(ReservationsServiceContract::class, ReservationsService::class);
        $this->getContainer()->bind(SummariesServiceContract::class, SummariesService::class);
        $this->getContainer()->singleton(ProductInventoryCachingServiceContract::class, ProductInventoryCachingService::class);
    }
}

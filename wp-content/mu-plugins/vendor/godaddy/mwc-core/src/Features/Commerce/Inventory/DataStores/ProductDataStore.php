<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\ProductsDataStore as CatalogProductsDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\MapAssetsHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores\Traits\CanCrudPlatformInventoryDataTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LevelMapRepository;

class ProductDataStore extends CatalogProductsDataStore
{
    use CanCrudPlatformInventoryDataTrait;

    /**
     * @param ProductsServiceContract $productsService
     * @param LevelsServiceContract $levelsService
     * @param LevelsServiceWithCacheContract $levelsServiceWithCache
     * @param SummariesServiceContract $summariesService
     * @param InventoryProviderContract $inventoryProvider
     * @param CommerceContextContract $commerceContext
     * @param MapAssetsHelper $mapAssetsHelper
     * @param LevelMapRepository $levelMapRepository
     */
    public function __construct(
        ProductsServiceContract $productsService,
        LevelsServiceContract $levelsService,
        LevelsServiceWithCacheContract $levelsServiceWithCache,
        SummariesServiceContract $summariesService,
        InventoryProviderContract $inventoryProvider,
        CommerceContextContract $commerceContext,
        MapAssetsHelper $mapAssetsHelper,
        LevelMapRepository $levelMapRepository
    ) {
        $this->levelsService = $levelsService;
        $this->levelsServiceWithCache = $levelsServiceWithCache;
        $this->summariesService = $summariesService;
        $this->inventoryProvider = $inventoryProvider;
        $this->commerceContext = $commerceContext;
        $this->mapAssetsHelper = $mapAssetsHelper;
        $this->levelMapRepository = $levelMapRepository;

        parent::__construct($productsService, $mapAssetsHelper);
    }
}

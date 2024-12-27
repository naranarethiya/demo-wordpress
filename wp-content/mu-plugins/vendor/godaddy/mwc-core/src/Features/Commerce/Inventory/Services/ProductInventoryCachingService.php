<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListLevelsByRemoteIdOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListSummariesOperation;

class ProductInventoryCachingService implements ProductInventoryCachingServiceContract
{
    protected LevelsCachingService $levelsCachingService;
    protected SummariesCachingService $summariesCachingService;
    protected SummariesServiceContract $summariesService;
    protected LevelsServiceContract $levelsService;

    /**
     * @param SummariesCachingService $summariesCachingService
     * @param LevelsCachingService $levelsCachingService
     * @param SummariesServiceContract $summariesService
     * @param LevelsServiceContract $levelsService
     */
    public function __construct(
        SummariesCachingService $summariesCachingService,
        LevelsCachingService $levelsCachingService,
        SummariesServiceContract $summariesService,
        LevelsServiceContract $levelsService
    ) {
        $this->summariesCachingService = $summariesCachingService;
        $this->levelsCachingService = $levelsCachingService;
        $this->summariesService = $summariesService;
        $this->levelsService = $levelsService;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshCache(array $productIds) : void
    {
        /*
         * @NOTE: two distinct methods that catch their own exceptions. This is to ensure that if one fails, the other is still attempted
         */
        $this->refreshSummariesCache($productIds);
        $this->refreshLevelsCache($productIds);
    }

    /**
     * Refreshes the summaries cache for the given product IDs.
     *
     * @param string[] $productIds
     */
    protected function refreshSummariesCache(array $productIds) : void
    {
        try {
            $summaries = $this->summariesService->withoutCache()->list(
                ListSummariesOperation::seed([
                    'productIds' => $productIds,
                ])
            )->getSummaries();

            $this->summariesCachingService->setMany($summaries);
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('Could not refresh summaries cache.', $exception);
        }
    }

    /**
     * Refreshes the levels cache for the given product IDs.
     *
     * @param string[] $productIds
     */
    protected function refreshLevelsCache(array $productIds) : void
    {
        try {
            $levels = $this->levelsService->listLevelsByRemoteProductId(
                ListLevelsByRemoteIdOperation::seed([
                    'ids' => $productIds,
                ])
            )->getLevels();

            $this->levelsCachingService->setMany($levels);
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('Could not refresh levels cache.', $exception);
        }
    }
}

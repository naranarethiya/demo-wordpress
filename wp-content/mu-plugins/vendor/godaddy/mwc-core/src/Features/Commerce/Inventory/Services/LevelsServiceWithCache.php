<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ListLevelsByRemoteIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLevelsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLevelsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ListLevelsResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ReadLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class LevelsServiceWithCache implements LevelsServiceWithCacheContract
{
    protected CommerceContextContract $commerceContext;
    protected InventoryProviderContract $provider;
    protected LevelsServiceContract $levelsService;
    protected LevelsCachingService $levelsCachingService;
    protected SummariesCachingService $summariesCachingService;
    protected ProductsMappingServiceContract $productsMappingService;

    /**
     * @param LevelsServiceContract $levelsService
     * @param LevelsCachingService $levelsCachingService
     * @param SummariesCachingService $summariesCachingService
     * @param CommerceContextContract $commerceContext
     * @param InventoryProviderContract $provider
     * @param ProductsMappingServiceContract $productsMappingService
     */
    public function __construct(
        LevelsServiceContract $levelsService,
        LevelsCachingService $levelsCachingService,
        SummariesCachingService $summariesCachingService,
        CommerceContextContract $commerceContext,
        InventoryProviderContract $provider,
        ProductsMappingServiceContract $productsMappingService
    ) {
        $this->commerceContext = $commerceContext;
        $this->levelsService = $levelsService;
        $this->levelsCachingService = $levelsCachingService;
        $this->summariesCachingService = $summariesCachingService;
        $this->provider = $provider;
        $this->productsMappingService = $productsMappingService;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        $response = $this->levelsService->createOrUpdateLevelWithRepair($operation);

        $level = $response->getLevel();

        // update the level cache
        $this->levelsCachingService->set($level);

        // clear the summary cache for the product
        $this->summariesCachingService->remove($level->productId);

        return $response;
    }

    /**
     * {@inheritDoc}
     *
     * @throws MissingProductRemoteIdException
     */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        $product = $operation->getProduct();

        if (! $existingRemoteProductId = $this->productsMappingService->getRemoteId($product)) {
            throw new MissingProductRemoteIdException('Could not get the remote product ID for given product');
        }

        $level = $this->levelsCachingService->remember(
            $existingRemoteProductId,
            fn () => $this->levelsService->readLevel($operation)->getLevel()
        );

        return new ReadLevelResponse($level);
    }

    /**
     * {@inheritDoc}
     */
    public function listLevelsByRemoteProductId(ListLevelsByRemoteIdOperationContract $operation) : ListLevelsResponseContract
    {
        $productIds = ArrayHelper::wrap($operation->getIds());

        $cachedLevels = $this->levelsCachingService->getMany($productIds);

        $uncachedProductIds = $this->getUncachedLevelsProductIds($productIds, $cachedLevels);

        if ($uncachedProductIds) {
            $levelsFromRemoteService = $this->listLevelsFromRemoteService($uncachedProductIds);

            $this->levelsCachingService->setMany($levelsFromRemoteService);

            $cachedLevels = array_merge($cachedLevels, $levelsFromRemoteService);
        }

        return ListLevelsResponse::getNewInstance($cachedLevels);
    }

    /**
     * Performs an array diff operation to identify what product IDs are not cached in the {@see Level}s list.
     *
     * @param string[] $productIds
     * @param Level[] $cachedLevels
     *
     * @return string[]
     */
    protected function getUncachedLevelsProductIds(array $productIds, array $cachedLevels) : array
    {
        return array_values(array_diff(
            $productIds,
            $this->levelsCachingService->getSkippedResourceIds(),
            array_map(static fn ($level) => $level->productId, $cachedLevels)
        ));
    }

    /**
     * Lists levels from the remote service.
     *
     * @param string[] $productIds
     *
     * @return Level[]
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    protected function listLevelsFromRemoteService(array $productIds) : array
    {
        $listLevelsInput = $this->getListLevelsInput($productIds);

        $gateway = $this->provider->levels();

        $foundLevels = $gateway->list($listLevelsInput);

        // mark any productIds that didn't return level data as skipped for future queries
        $this->levelsCachingService->addSkippedResourceIds($this->getUncachedLevelsProductIds($productIds, $foundLevels));

        return $foundLevels;
    }

    /**
     * @param string[] $productIds
     *
     * @return ListLevelsInput
     */
    protected function getListLevelsInput(array $productIds) : ListLevelsInput
    {
        return ListLevelsInput::getNewInstance([
            'storeId'    => $this->commerceContext->getStoreId(),
            'productIds' => $productIds,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @note NO-OP
     */
    public function createOrUpdateLevelWithRepair(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        throw new Exception('Unimplemented method, use LevelsService::createOrUpdateLevelWithRepair instead');
    }

    /**
     * {@inheritDoc}
     *
     * @note NO-OP
     *
     * @throws Exception
     */
    public function readLevelWithRepair(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        throw new Exception('Unimplemented method, use LevelsService::readLevelWithRepair instead');
    }

    /**
     * {@inheritDoc}
     */
    public function mapLevelToProduct(array $levels, Product $product) : void
    {
        $this->levelsService->mapLevelToProduct($levels, $product);
    }
}

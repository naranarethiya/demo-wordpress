<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLevelRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\NotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ListLevelsByRemoteIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLevelsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters\LevelAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListLevelsByRemoteIdOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLevelsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\CreateOrUpdateLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ListLevelsResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ReadLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class LevelsService implements LevelsServiceContract
{
    protected CommerceContextContract $commerceContext;
    protected InventoryProviderContract $provider;
    protected LevelMappingServiceContract $levelMappingService;
    protected LocationMappingServiceContract $locationMappingService;
    protected ProductsMappingServiceContract $productMappingService;

    /**
     * @param CommerceContextContract $commerceContext
     * @param InventoryProviderContract $provider
     * @param LevelMappingServiceContract $levelMappingService
     * @param LocationMappingServiceContract $locationMappingService
     * @param ProductsMappingServiceContract $productMappingService
     */
    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProviderContract $provider,
        LevelMappingServiceContract $levelMappingService,
        LocationMappingServiceContract $locationMappingService,
        ProductsMappingServiceContract $productMappingService
    ) {
        $this->commerceContext = $commerceContext;
        $this->provider = $provider;
        $this->levelMappingService = $levelMappingService;
        $this->locationMappingService = $locationMappingService;
        $this->productMappingService = $productMappingService;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        $product = $operation->getProduct();

        $existingRemoteId = $this->levelMappingService->getRemoteId($product);

        // create or update in the inventory service
        $level = $this->provider->levels()->createOrUpdate(
            $this->getUpsertLevelInput($product, $existingRemoteId)
        );

        if (! $level->inventoryLevelId) {
            throw MissingLevelRemoteIdException::withDefaultMessage();
        }

        // save the remote ID if not done already
        if (! $existingRemoteId) {
            $this->levelMappingService->saveRemoteId($product, $level->inventoryLevelId);
        }

        return new CreateOrUpdateLevelResponse($level);
    }

    /**
     * Gets the upsert level input.
     *
     * @param Product $product
     * @param string|null $existingRemoteId
     *
     * @return UpsertLevelInput
     * @throws MissingProductRemoteIdException
     */
    protected function getUpsertLevelInput(Product $product, ?string $existingRemoteId) : UpsertLevelInput
    {
        return new UpsertLevelInput([
            'storeId' => $this->commerceContext->getStoreId(),
            'level'   => $this->buildLevelData($product, $existingRemoteId),
        ]);
    }

    /**
     * Builds a level from the given product.
     *
     * @param Product $product
     * @param string|null $remoteId
     *
     * @return Level
     * @throws MissingProductRemoteIdException
     */
    protected function buildLevelData(Product $product, ?string $remoteId) : Level
    {
        /** @var Level $level */
        $level = LevelAdapter::getNewInstance()->convertToSource($product);
        $locationId = $this->locationMappingService->getRemoteId() ?? null;
        $productId = $this->productMappingService->getRemoteId($product);

        if (! $productId) {
            throw new MissingProductRemoteIdException('The level product has no remote UUID saved');
        }

        $level->inventoryLevelId = $remoteId;
        $level->inventoryLocationId = $locationId;
        $level->productId = $productId;

        return $level;
    }

    /**
     * {@inheritDoc}
     */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        $product = $operation->getProduct();

        if (! $existingRemoteLevelId = $this->levelMappingService->getRemoteId($product)) {
            throw new MissingLevelRemoteIdException('Could not get the remote level ID for given product');
        }

        $level = $this->provider->levels()->read(ReadLevelInput::getNewInstance([
            'storeId' => $this->commerceContext->getStoreId(),
            'levelId' => $existingRemoteLevelId,
        ]));

        return new ReadLevelResponse($level);
    }

    /**
     * {@inheritDoc}
     */
    public function listLevelsByRemoteProductId(ListLevelsByRemoteIdOperationContract $operation) : ListLevelsResponseContract
    {
        $levels = $this->provider->levels()->list($this->getListLevelsInput(ArrayHelper::wrap($operation->getIds())));

        return new ListLevelsResponse($levels);
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
     */
    public function createOrUpdateLevelWithRepair(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        try {
            return $this->createOrUpdateLevel($operation);
        } catch (NotUniqueException $exception) {
            $this->listAndMap($operation->getProduct());

            return $this->createOrUpdateLevel($operation);
        }
    }

    /**
     * Read a level with ability to repair.
     *
     * @param ReadLevelOperationContract $operation
     *
     * @return ReadLevelResponseContract
     * @throws CommerceExceptionContract|Exception
     */
    public function readLevelWithRepair(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        try {
            return $this->readLevel($operation);
        } catch (MissingLevelRemoteIdException $exception) {
            $this->listAndMap($operation->getProduct());

            return $this->readLevel($operation);
        }
    }

    /**
     * List levels by product.
     *
     * @return Level[]
     *
     * @throws BaseException|CommerceExceptionContract|Exception
     */
    protected function listByProduct(Product $product) : array
    {
        $remoteId = $this->productMappingService->getRemoteId($product);

        if (! $remoteId) {
            return [];
        }

        return $this
            ->listLevelsByRemoteProductId(ListLevelsByRemoteIdOperation::seed(['ids' => [$remoteId]]))
            ->getLevels();
    }

    /**
     * {@inheritDoc}
     *
     * @throws CommerceExceptionContract
     */
    public function mapLevelToProduct(array $levels, Product $product) : void
    {
        $productRemoteId = $this->productMappingService->getRemoteId($product);
        $locationRemoteId = $this->locationMappingService->getRemoteId();

        foreach ($levels as $level) {
            if ($this->shouldMapLevelToProduct($level, $productRemoteId, $locationRemoteId)) {
                // the ?? check below is to avoid phpstan warnings -- at this point, the level has an inventory level ID
                $this->levelMappingService->saveRemoteId($product, $level->inventoryLevelId ?? '');

                return;
            }
        }

        throw MissingLevelRemoteIdException::withDefaultMessage();
    }

    /**
     * Determines whether a level should be mapped to a product.
     *
     * @param Level $level
     * @param string|null $productRemoteId
     * @param string|null $locationRemoteId
     *
     * @return bool
     */
    protected function shouldMapLevelToProduct(Level $level, ?string $productRemoteId, ?string $locationRemoteId) : bool
    {
        return
            ! empty($level->inventoryLevelId) &&
            $level->productId === $productRemoteId &&
            (null === $locationRemoteId || $level->inventoryLocationId === $locationRemoteId);
    }

    /**
     * Lists and maps a product to a level.
     *
     * @param Product $product
     *
     * @throws MissingLevelRemoteIdException|CommerceExceptionContract|Exception
     */
    protected function listAndMap(Product $product) : void
    {
        $this->mapLevelToProduct($this->listByProduct($product), $product);
    }
}

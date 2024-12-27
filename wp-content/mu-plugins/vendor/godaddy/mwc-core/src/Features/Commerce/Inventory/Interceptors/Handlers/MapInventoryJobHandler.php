<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLevelRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListLevelsByRemoteIdOperation;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class MapInventoryJobHandler extends AbstractInterceptorHandler
{
    use CanGetNewInstanceTrait;

    public const JOB_NAME = 'mwc_gd_commerce_inventory_map';

    /** @var LevelsServiceWithCacheContract the injected level service instance */
    protected LevelsServiceWithCacheContract $levelsService;

    public function __construct(LevelsServiceWithCacheContract $levelsService)
    {
        $this->levelsService = $levelsService;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        if (! isset($args[0]) || ! $remoteIds = TypeHelper::arrayOfStrings($args[0])) {
            return;
        }

        try {
            $levels = $this->levelsService->listLevelsByRemoteProductId(ListLevelsByRemoteIdOperation::seed([
                'ids' => $remoteIds,
            ]))->getLevels();

            foreach ($this->getProducts($remoteIds) as $product) {
                $this->levelsService->mapLevelToProduct($levels, $product);
            }
        } catch (MissingLevelRemoteIdException $exception) {
            // indicates that the level doesn't exist on the platform -- we do not need to report this
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('Could not list and map inventory levels', $exception);
        }
    }

    /**
     * Returns populated Product instances.
     *
     * @param array<int, string> $productIds
     * @return Product[]
     */
    protected function getProducts(array $productIds) : array
    {
        $products = [];

        foreach ($productIds as $localId => $remoteId) {
            $products[] = Product::getNewInstance()->setId($localId);
        }

        return $products;
    }

    /**
     * Schedules the job with the supplied product IDs.
     *
     * @param string[] $productIds
     *
     * @throws InvalidScheduleException
     */
    protected static function scheduleJob(array $productIds) : void
    {
        Schedule::singleAction()
            ->setName(static::JOB_NAME)
            ->setArguments($productIds)
            ->setScheduleAt(new DateTime())
            ->schedule();
    }

    /**
     * Schedules jobs in chunks with the supplied product associations.
     *
     * @param ProductAssociation[] $productAssociations
     */
    public static function scheduleJobs(array $productAssociations) : void
    {
        $chunks = static::extractIdsInChunks($productAssociations, 100);

        foreach ($chunks as $chunk) {
            try {
                static::scheduleJob($chunk);
            } catch (InvalidScheduleException $e) {
                SentryException::getNewInstance('Could not schedule a map inventory job', $e);
            }
        }
    }

    /**
     * Gets an array of product IDs in chunks.
     *
     * @param ProductAssociation[] $productAssociations
     * @param positive-int $chunkLength
     *
     * @return array<int, array<int, string>>
     */
    protected static function extractIdsInChunks(array $productAssociations, int $chunkLength) : array
    {
        $productIds = [];

        foreach ($productAssociations as $productAssociation) {
            $productIds[$productAssociation->localId] = $productAssociation->remoteResource->productId;
        }

        return array_chunk(array_unique(array_filter($productIds)), $chunkLength, true);
    }
}

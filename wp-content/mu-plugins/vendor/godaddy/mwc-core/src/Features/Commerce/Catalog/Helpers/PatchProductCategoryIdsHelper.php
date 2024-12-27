<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\DispatchJobToSaveLocalProductSubscriber;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\SaveLocalProductAfterRemoteUpdateInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\PatchProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ReadProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Class to help patch a product's categoryIds in the remote platform.
 */
class PatchProductCategoryIdsHelper
{
    protected CategoryMapRepository $categoryMapRepository;
    protected ProductsServiceContract $productsService;
    protected ProductsMappingServiceContract $productsMappingService;

    public function __construct(
        CategoryMapRepository $categoryMapRepository,
        ProductsServiceContract $productsService,
        ProductsMappingServiceContract $productsMappingService
    ) {
        $this->categoryMapRepository = $categoryMapRepository;
        $this->productsService = $productsService;
        $this->productsMappingService = $productsMappingService;
    }

    /**
     * Patches a product's `categoryIds` in the remote platform, if it's eligible.
     *
     * This also reconciles the local and remote category IDs to ensure we don't accidentally overwrite the remote ones
     * with the local ones (in case they differ). Or goal here is to always _add_ the local categories to the remote --
     * not do a full replacement.
     *
     * @param WC_Product $wcProduct
     * @return void
     * @throws CommerceExceptionContract|GatewayRequest404Exception|GatewayRequestException|MissingProductRemoteIdException|CachingStrategyException
     * @throws ProductNotFoundException
     */
    public function maybePatch(WC_Product $wcProduct) : void
    {
        if (! $remoteProductId = $this->getRemoteProductId($wcProduct)) {
            // this means the product doesn't exist in the service yet, so we don't need to patch it
            return;
        }

        $localCategoryIds = TypeHelper::arrayOfIntegers($wcProduct->get_category_ids('edit'));

        // convert the local Woo IDs into their remote UUID counterparts
        // these are the UUIDs we're aware of locally
        $locallyKnownCategoryUuids = ! empty($localCategoryIds) ? $this->getRemoteCategoryIdsFromLocalIds($localCategoryIds) : [];

        // known local UUIDs combined with what's upstream
        $combinedCategoryUuids = $this->buildReconciledRemoteCategoryIdsForProduct($wcProduct->get_id(), $locallyKnownCategoryUuids);

        $this->saveLocalProductIfMissingAssociations($wcProduct->get_id(), $locallyKnownCategoryUuids, $combinedCategoryUuids);

        if (empty($combinedCategoryUuids)) {
            // this means the product is not associated with any categories
            return;
        }

        $operation = PatchProductOperation::seed([
            'categoryIds'    => $combinedCategoryUuids,
            'localProductId' => $wcProduct->get_id(),
        ]);

        $this->productsService->patchProduct($operation, $remoteProductId);
    }

    /**
     * Gets the remote product UUID that corresponds to this local product instance.
     *
     * @param WC_Product $wcProduct
     * @return string|null
     */
    protected function getRemoteProductId(WC_Product $wcProduct) : ?string
    {
        return $this->productsMappingService->getRemoteId((new Product())->setId($wcProduct->get_id()));
    }

    /**
     * Combines the locally known category IDs with the upstream array.
     *
     * @param int $localProductId
     * @param string[] $remoteCategoryIds
     * @return string[]
     */
    protected function buildReconciledRemoteCategoryIdsForProduct(int $localProductId, array $remoteCategoryIds) : array
    {
        // fetch the product upstream so we can determine the category IDs already saved there
        // we do this because we want to _add_ our local categories to what already exists upstream
        try {
            $response = $this->productsService->readProduct(
                ReadProductOperation::getNewInstance()->setLocalId($localProductId)
            );
        } catch(Exception|CommerceExceptionContract $e) {
            return $remoteCategoryIds;
        }

        // combine the local and remote arrays
        if ($response->getProduct()->categoryIds) {
            $remoteCategoryIds = array_unique(array_merge($remoteCategoryIds, $response->getProduct()->categoryIds));
        }

        return array_values($remoteCategoryIds);
    }

    /**
     * Gets the corresponding remote category UUIDs that match the supplied local IDs.
     *
     * @param int[] $localIds
     * @return string[]
     */
    protected function getRemoteCategoryIdsFromLocalIds(array $localIds) : array
    {
        return $this->categoryMapRepository
            ->getMappingsByLocalIds($localIds)
            ->getRemoteIds();
    }

    /**
     * Schedules an async job to save the product locally, if it's missing product <=> category associations from upstream.
     *
     * This is a way of pulling down associations that exist in the API but not yet locally. After this backfill process,
     * this will be handled via change detection {@see DispatchJobToSaveLocalProductSubscriber}. This is just needed to
     * sync up any associations that were created prior to introducing association reads and writes.
     *
     * @param int $localProductId
     * @param string[] $locallyKnownCategoryUuids
     * @param string[] $combinedCategoryUuids
     * @return void
     */
    protected function saveLocalProductIfMissingAssociations(int $localProductId, array $locallyKnownCategoryUuids, array $combinedCategoryUuids) : void
    {
        sort($locallyKnownCategoryUuids);
        sort($combinedCategoryUuids);

        // if we have the same categories, then we don't need to do anything; it means local and remote match
        if ($locallyKnownCategoryUuids === $combinedCategoryUuids) {
            return;
        }

        try {
            $job = Schedule::singleAction()
                ->setName(SaveLocalProductAfterRemoteUpdateInterceptor::JOB_NAME)
                ->setArguments($localProductId)
                ->setScheduleAt(new DateTime('now'));

            if (! $job->isScheduled()) {
                $job->schedule();
            }
        } catch(Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);
        }
    }
}

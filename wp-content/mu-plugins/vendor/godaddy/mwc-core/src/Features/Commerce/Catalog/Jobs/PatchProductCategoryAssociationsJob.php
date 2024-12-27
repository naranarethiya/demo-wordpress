<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Jobs;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\PatchProductCategoryIdsHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\PatchProductCategoryIdsJobStatusHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\RemoteProductNotFoundHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundException;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\BatchJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\HasJobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobOutcome;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobSettings;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\BatchJobTrait;
use WC_Product;

/**
 * A batch job that queries all products in the local database and executes PATCH requests to the platform with the
 * product's associated category IDs.
 *
 * Initially when products were written to the platform we did not send {@see ProductBase::$categoryIds} data. This job
 * backfills that data for all products we had previously written.
 *
 * @method BatchJobSettings getJobSettings()
 */
class PatchProductCategoryAssociationsJob implements QueueableJobContract, BatchJobContract, HasJobSettingsContract
{
    use BatchJobTrait {
        handle as traitHandle;
    }

    public const JOB_KEY = 'patchProductCategoryAssociations';

    protected PatchProductCategoryIdsHelper $patchProductCategoryIdsHelper;
    protected ProductsServiceContract $productsService;
    protected RemoteProductNotFoundHelper $remoteProductNotFoundHelper;

    public function __construct(PatchProductCategoryIdsHelper $patchProductCategoryIdsHelper, ProductsServiceContract $productsService, RemoteProductNotFoundHelper $remoteProductNotFoundHelper)
    {
        $this->patchProductCategoryIdsHelper = $patchProductCategoryIdsHelper;
        $this->productsService = $productsService;
        $this->remoteProductNotFoundHelper = $remoteProductNotFoundHelper;

        $this->setJobSettings($this->configureJobSettings());
    }

    /**
     * {@inheritDoc}
     */
    public function getJobKey() : string
    {
        return static::JOB_KEY;
    }

    /**
     * {@inheritDoc}
     */
    public function handle() : void
    {
        // override the trait method so we can bail early if the catalog integration is not enabled
        // this check is necessary in case the job is being dispatched from a CLI command
        if (! CatalogIntegration::isEnabled()) {
            $this->jobDone();

            return;
        }

        $this->traitHandle();
    }

    /**
     * {@inheritDoc}
     */
    protected function processBatch() : BatchJobOutcome
    {
        $wooProducts = $this->getLocalProductsBatch();

        $this->setAttemptedResourcesCount(count($wooProducts));

        try {
            $this->patchProducts($wooProducts);
        } catch(MissingRemoteIdsAfterLocalIdConversionException $e) {
            // this means none of the local products exist in the platform, so we don't have to bother patching anything in this batch
        }

        $this->incrementOffsetForNextBatch();

        return $this->makeOutcome();
    }

    /**
     * {@inheritDoc}
     */
    protected function onAllBatchesCompleted() : void
    {
        PatchProductCategoryIdsJobStatusHelper::setHasRun();
    }

    /**
     * Gets the offset to use in the query. This is incremented each batch run.
     *
     * @return int
     */
    protected function getOffsetForBatch() : int
    {
        return max(TypeHelper::int(ArrayHelper::get($this->args, 'offset'), 0), 0);
    }

    /**
     * Increments the query offset in preparation for the next batch.
     *
     * @return void
     */
    protected function incrementOffsetForNextBatch() : void
    {
        $currentOffset = $this->getOffsetForBatch();

        if (! is_array($this->args)) {
            $this->args = [];
        }

        $this->args['offset'] = $currentOffset + $this->getJobSettings()->maxPerBatch;
    }

    /**
     * Queries for a batch of local products.
     *
     * @return WC_Product[]
     */
    protected function getLocalProductsBatch() : array
    {
        /** @var WC_Product[] $products */
        $products = CatalogIntegration::withoutReads(function () {
            return ProductsRepository::query([
                'offset'         => $this->getOffsetForBatch(),
                'posts_per_page' => $this->getJobSettings()->maxPerBatch,
            ]);
        });

        return $products;
    }

    /**
     * Loops through all the found products to patch them if required.
     *
     * @param WC_Product[] $wooProducts
     * @return void
     * @throws MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function patchProducts(array $wooProducts) : void
    {
        $hasReportedException = false;

        $this->preWarmRemoteProductCache($wooProducts);

        foreach ($wooProducts as $localProduct) {
            try {
                $this->patchProductCategoryIdsHelper->maybePatch($localProduct);
            } catch(ProductNotFoundException $e) {
                // means the product was deleted upstream, delete it locally
                $this->remoteProductNotFoundHelper->handle($localProduct->get_id());
            } catch (Exception|CommerceExceptionContract $e) {
                // We only want to report max 1 exception (prevents excessive spam/reporting in case all fail for some reason)
                if (! $hasReportedException) {
                    SentryException::getNewInstance('Failed to patch product categoryIds: '.$e->getMessage(), $e);
                    $hasReportedException = true;
                }
            }
        }
    }

    /**
     * Queries the platform for the provided products in order to pre-warm the cache.
     * This prevents N+1 issues later in {@see PatchProductCategoryIdsHelper::buildReconciledRemoteCategoryIdsForProduct()}
     * when we query for the remote product's list of categories.
     *
     * @param WC_Product[] $wooProducts
     * @return void
     * @throws MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function preWarmRemoteProductCache(array $wooProducts) : void
    {
        try {
            $localIds = array_map(fn (WC_Product $wooProduct) => $wooProduct->get_id(), $wooProducts);

            // we're just pre-warming the cache to prevent N+1 issues later; we don't need to do anything with the results
            $this->productsService->listProductsByLocalIds($localIds);
        } catch(MissingRemoteIdsAfterLocalIdConversionException $e) {
            // this means none of the local products exist upstream, which means there's no point in patching any of these!
            // we'll let it bubble up so we can bail out of the patching attempt
            throw $e;
        } catch(Exception|CommerceExceptionContract $e) {
            // catching this because we don't need this to be a blocker to the rest of the process
            SentryException::getNewInstance('Failed to pre-warm remote product cache during patch job.', $e);
        }
    }
}

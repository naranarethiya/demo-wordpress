<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\WriteProductService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\SkippedProductsRepository;
use WC_Product;

/**
 * Products backfill job.
 */
class BackfillProductsJob extends AbstractBackfillResourceJob
{
    /** @var string unique identifier for the queue.jobs config */
    public const JOB_KEY = 'backfillProducts';

    /** @var ProductMapRepository */
    protected ProductMapRepository $productMapRepository;

    /** @var WriteProductService service to aid in writing products */
    protected WriteProductService $writeProductService;

    /**
     * Constructor.
     *
     * @param ProductMapRepository $productMapRepository
     * @param SkippedProductsRepository $skippedProductsRepository
     * @param WriteProductService $writeProductService
     */
    public function __construct(
        ProductMapRepository $productMapRepository,
        SkippedProductsRepository $skippedProductsRepository,
        WriteProductService $writeProductService
    ) {
        $this->productMapRepository = $productMapRepository;
        $this->writeProductService = $writeProductService;

        parent::__construct($skippedProductsRepository);
    }

    /**
     * Queries for the local {@see WC_Product} product objects.
     *
     * @return WC_Product[]|null
     */
    protected function getLocalResources() : ?array
    {
        $localIds = $this->productMapRepository->getUnmappedLocalIds($this->getJobSettings()->maxPerBatch);

        if (empty($localIds)) {
            return null;
        }

        /** @var WC_Product[] $products */
        $products = CatalogIntegration::withoutReads(function () use ($localIds) {
            return ProductsRepository::query([
                'include'        => $localIds,
                'posts_per_page' => $this->getJobSettings()->maxPerBatch,
            ]);
        });

        $this->setAttemptedResourcesCount(count($products));

        return $products;
    }

    /**
     * Creates a resource in the platform if it's eligible. Logs ineligible and failed items.
     *
     * @param WC_Product $resource
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function maybeCreateResourceInPlatform($resource) : void
    {
        try {
            if ($resource->get_meta('wpnux_id')) {
                throw new Exception('Demo product ineligible for writing.');
            }

            $this->writeProductService->write($resource);
        } catch(Exception $e) {
            $this->markLocalResourceAsSkipped($resource->get_id());
        }
    }

    /**
     * Since the catalog integration also writes inventory data, we need to ensure that both capabilities are present.
     *
     * @return bool
     */
    protected function hasWriteCapability() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE)
            && InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE);
    }
}

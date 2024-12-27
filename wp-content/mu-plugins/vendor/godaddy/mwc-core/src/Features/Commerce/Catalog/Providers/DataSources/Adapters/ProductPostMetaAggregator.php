<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\UpdateProductMetaCacheHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListSummariesOperation;

/**
 * @TODO in the future try to decouple the inventory logic MWC-12698 {agibson 2023-06-14) -- note moved from UpdateProductMetaCacheHandler
 */
class ProductPostMetaAggregator
{
    protected SummariesServiceContract $summariesService;

    public function __construct(SummariesServiceContract $summariesService)
    {
        $this->summariesService = $summariesService;
    }

    /**
     * Combines the given metadata with metadata generated from the remote product information.
     *
     * @param array<string, array<?string>> $meta
     * @param ProductBase $remoteProduct
     * @return array<string, array<?string>>
     */
    public function aggregate(array $meta, ProductBase $remoteProduct) : array
    {
        $inventorySummary = $this->getInventorySummaryForProduct($remoteProduct);

        return array_merge(
            $meta,
            ProductPostMetaAdapter::getNewInstance($remoteProduct)
                ->setLocalMeta($meta)
                ->setInventorySummary($inventorySummary)
                ->convertFromSourceToFormattedArray()
        );
    }

    /**
     * Gets the inventory summary (if available) that corresponds to the supplied product.
     *
     * @param ProductBase $remoteProduct
     *
     * @return Summary|null
     */
    protected function getInventorySummaryForProduct(ProductBase $remoteProduct) : ?Summary
    {
        // bail if not tracking inventory
        if (! isset($remoteProduct->inventory) || ! $remoteProduct->inventory->tracking || ! $remoteProduct->inventory->externalService) {
            return null;
        }

        // bail if the inventory feature reads are disabled
        if (! InventoryIntegration::shouldLoad() || ! InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ)) {
            return null;
        }

        try {
            // the actual summary will very likely have been cached at this point, and that cache will be returned instead of calling the API
            $summaries = $this->summariesService->list(ListSummariesOperation::seed([
                'productIds' => [$remoteProduct->productId],
            ]))->getSummaries();

            $productSummary = current($summaries);

            return $productSummary instanceof Summary ? $productSummary : null;
        } catch (Exception $exception) {
            SentryException::getNewInstance("Could not read inventory summary for product {$remoteProduct->productId}", $exception);

            return null;
        }
    }
}

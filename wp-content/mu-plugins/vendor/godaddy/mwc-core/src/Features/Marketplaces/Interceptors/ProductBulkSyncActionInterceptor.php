<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\SyncJob;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ProductBulkSyncSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Callback for processing the product sync job.
 *
 * The job gets created in {@see ProductBulkSyncSubscriber} and this class is responsible for actually handling the job.
 */
class ProductBulkSyncActionInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('mwc_push_marketplaces_objects')
            ->setHandler([$this, 'maybeHandlePushJob'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Handles the push job if the requirements are met.
     *
     * @param int $jobId ID of the job.
     * @param int[] $objectIds Array of product IDs to sync.
     * @return void
     * @throws Exception
     */
    public function maybeHandlePushJob(int $jobId, array $objectIds) : void
    {
        if (empty($objectIds) || ! ($job = PushSyncJob::get($jobId)) || 'product' !== $job->getObjectType()) {
            return;
        }

        $this->handlePushProductsJob($job, $objectIds);
    }

    /**
     * Syncs the products with GoDaddy Marketplaces. This sync is done by calling {@see Product::update()} on each
     * object, which then fires an event to EventBridge. Marketplaces retrieves the data from there.
     *
     * @param SyncJob $job Job model.
     * @param array $productIds Array of product IDs to sync.
     * @return void
     * @throws Exception
     */
    protected function handlePushProductsJob(SyncJob $job, array $productIds) : void
    {
        $this->setEventContext();

        foreach ($productIds as $productId) {
            $this->syncProduct((int) $productId);
        }

        $updatedIds = ArrayHelper::combine($job->getUpdatedIds(), $productIds);

        $jobData = [
            'updatedIds' => $updatedIds,
        ];

        if ($updatedIds === $job->getObjectIds()) {
            $jobData['status'] = 'complete';
        }

        $job->update($jobData);
    }

    /**
     * Sets the context of model events to `sync`.
     *
     * @return void
     */
    protected function setEventContext() : void
    {
        if (! defined('MWC_EVENT_CONTEXT')) {
            define('MWC_EVENT_CONTEXT', 'sync');
        }
    }

    /**
     * Syncs a product by firing a "product updated" event.
     *
     * @param int $productId ID of the product we're syncing
     * @return void
     * @throws Exception
     */
    protected function syncProduct(int $productId) : void
    {
        $product = ProductsRepository::get($productId);

        if (! $product instanceof WC_Product) {
            return;
        }

        ProductAdapter::getNewInstance($product)->convertFromSource()->update();
    }
}

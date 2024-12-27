<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\SyncJob;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\ProductDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidProductException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\CatalogsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\ProductsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Push;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class PushProductsActionInterceptor extends AbstractInterceptor
{
    /** @var ProductsGateway products gateway instance */
    protected $productsGateway;

    /** @var CatalogsGateway catalogs gateway instance */
    protected $catalogsGateway;

    /** @var ProductDataStore data store instance */
    protected $dataStore;

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return WooCommerceRepository::isWooCommerceActive();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('mwc_push_poynt_objects')
            ->setHandler([$this, 'maybeHandlePushJob'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Possibly handles a push job.
     *
     * @param int $jobId
     * @param int[] $objectIds
     * @return void
     * @throws Exception
     */
    public function maybeHandlePushJob(int $jobId, array $objectIds)
    {
        if (empty($objectIds) || ! ($job = PushSyncJob::get($jobId)) || 'product' !== $job->getObjectType()) {
            return;
        }

        $this->handlePushProductsJob($job, $objectIds);
    }

    /**
     * Handles the job to push products to the Poynt API.
     *
     * @param SyncJob $job
     * @param int[] $productIds
     * @return void
     * @throws Exception
     */
    protected function handlePushProductsJob(SyncJob $job, array $productIds)
    {
        $pushedProducts = $this->pushProductsToPoynt($job, $productIds);

        $this->addProductsToPoyntCatalogs($job, $pushedProducts);

        Push::setIsSyncing(false);

        if (! empty($job->getErrors())) {
            Push::setIsHealthy(true);
        }

        $job->update([
            // TODO: consider distinguishing between products that were remotely created vs. updated. This might be possible
            //  by checking if the product had the remoteId before it was pushed to Poynt {@itambek 2022-02-09}
            'updatedIds' => array_map(static function ($product) {
                return $product->getId();
            }, $pushedProducts),
            'status' => 'complete',
        ]);
    }

    /**
     * Pushes products to Poynt.
     *
     * @param SyncJob $job
     * @param int[] $productIds
     * @return Product[] Array of products that were successfully pushed to Poynt
     * @throws Exception
     */
    protected function pushProductsToPoynt(SyncJob $job, array $productIds) : array
    {
        return array_values(array_filter(array_map(function ($productId) use ($job) {
            return $this->pushProductToPoynt($job, $productId);
        }, $productIds)));
    }

    /**
     * Pushes a product to Poynt.
     *
     * @param SyncJob $job
     * @param int $productId
     * @return Product|null
     * @throws Exception
     */
    protected function pushProductToPoynt(SyncJob $job, int $productId)
    {
        try {
            $product = $this->getDataStore()->read($productId);

            if (! $product) {
                throw new InvalidProductException('Product not found');
            }

            return $this->getDataStore()->save($this->getProductsGateway()->upsert($product));
        } catch (Exception $exception) {
            $job->addErrors(ArrayHelper::wrap("Unable to push product {$productId} to Poynt: {$exception->getMessage()}"));

            Push::setIsHealthy(false);
        }

        return null;
    }

    /**
     * Adds the given products to the configured Poynt catalogs.
     *
     * @param SyncJob $job
     * @param Product[] $products
     * @return void
     * @throws Exception
     */
    protected function addProductsToPoyntCatalogs(SyncJob $job, array $products)
    {
        foreach (Push::getEnabledCatalogIds() as $catalogId) {
            $this->addProductsToPoyntCatalog($job, $products, $catalogId);
        }
    }

    /**
     * @param SyncJob $job
     * @param array $products
     * @param string $catalogId
     * @return void
     * @throws Exception
     */
    protected function addProductsToPoyntCatalog(SyncJob $job, array $products, string $catalogId)
    {
        try {
            $this->getCatalogsGateway()->addProducts($catalogId, $products);

            // ensure the record is updated that this catalog was synced
            Push::setSyncedCatalogIds(
                TypeHelper::arrayOfStrings(
                    array_unique(ArrayHelper::combine(Push::getSyncedCatalogIds(), [$catalogId]))
                )
            );
        } catch (Exception $exception) {
            $job->addErrors(ArrayHelper::wrap("Unable to add products to Poynt catalog {$catalogId}: {$exception->getMessage()}"));

            Push::setIsHealthy(false);
        }
    }

    /**
     * Gets the ProductDataStore instance.
     *
     * @return ProductDataStore
     */
    protected function getDataStore() : ProductDataStore
    {
        return $this->dataStore ?: $this->dataStore = new ProductDataStore('poynt');
    }

    /**
     *  Gets the ProductsGateway instance.
     *
     * @return ProductsGateway
     */
    protected function getProductsGateway() : ProductsGateway
    {
        return $this->productsGateway ?: $this->productsGateway = new ProductsGateway();
    }

    /**
     * Gets the CatalogsGateway instance.
     *
     * @return CatalogsGateway
     */
    protected function getCatalogsGateway() : CatalogsGateway
    {
        return $this->catalogsGateway ?: $this->catalogsGateway = new CatalogsGateway();
    }
}

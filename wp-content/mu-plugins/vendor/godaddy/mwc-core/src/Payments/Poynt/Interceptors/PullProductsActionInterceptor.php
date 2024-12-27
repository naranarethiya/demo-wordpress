<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\SyncJob;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\CatalogsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Category;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\AbstractSyncHandler;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Pull;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Traits\HandlesRemoteProductsTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class PullProductsActionInterceptor extends AbstractInterceptor
{
    use HandlesRemoteProductsTrait;

    /** @var string[] any error messages to add to the current job */
    protected $errors = [];

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
            ->setGroup('mwc_pull_poynt_objects')
            ->setHandler([$this, 'maybeHandlePullJob'])
            ->execute();
    }

    /**
     * Possibly handles a pull job.
     *
     * @param int $jobId
     * @throws Exception
     */
    public function maybeHandlePullJob(int $jobId)
    {
        if (! ($job = SyncJob::get($jobId)) || 'product' !== $job->getObjectType()) {
            return;
        }

        $handledProducts = ArrayHelper::flatten(array_map(function ($catalogId) {
            return $this->handleCatalog($catalogId);
        }, Pull::getEnabledCatalogIds()));

        Pull::setIsSyncing(false);

        if (empty($this->errors)) {
            Pull::setIsHealthy(true);
        }

        $job->update([
            'status'     => 'complete',
            'errors'     => $this->errors,
            'updatedIds' => array_map(function ($product) {
                return $product->getId() ?? $product->getRemoteId();
            }, $handledProducts),
        ]);
    }

    /**
     * Handles a single catalog.
     *
     * @param string  $catalogId
     * @return Product[]
     * @throws Exception
     */
    protected function handleCatalog(string $catalogId) : array
    {
        try {
            $catalog = CatalogsGateway::getNewInstance()->get($catalogId);

            $productIds = ArrayHelper::combine(
                $this->handleRemoteProducts($catalog->getProducts()),
                $this->handleRemoteCategories($catalog->getCategories())
            );

            // ensure the record is updated that this catalog was synced
            Pull::setSyncedCatalogIds(
                TypeHelper::arrayOfStrings(
                    array_unique(ArrayHelper::combine(Pull::getSyncedCatalogIds(), [$catalogId]))
                )
            );

            return $productIds;
        } catch (Exception $exception) {
            $this->errors[] = __("Catalog {$catalogId} could not be synced: {$exception->getMessage()}", 'mwc-core');

            Pull::setIsHealthy(false);

            return [];
        }
    }

    /**
     * Handles a list of remote categories.
     *
     * @param Category[] $categories
     * @return Product[]
     * @throws Exception
     */
    protected function handleRemoteCategories(array $categories) : array
    {
        return ArrayHelper::flatten((array_map(function ($category) {
            return $this->handleRemoteProducts($category->getProducts());
        }, $categories)));
    }

    /**
     * Handles a list of remote products.
     *
     * Returns the products that were successfully handled.
     *
     * @param Product[] $products
     * @return Product[]
     */
    protected function handleRemoteProducts(array $products) : array
    {
        $handledProducts = [];

        foreach ($products as $remoteProduct) {
            if (! AbstractSyncHandler::shouldSyncProduct($remoteProduct)) {
                continue;
            }

            try {
                $handledProducts[] = $this->handleRemoteProduct($remoteProduct);
            } catch (Exception $exception) {
                $this->errors[] = __("Product {$remoteProduct->getRemoteId()} could not be synced: {$exception->getMessage()}", 'mwc-core');

                Pull::setIsHealthy(false);
            }
        }

        return $handledProducts;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\ProductDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\CatalogsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Catalog;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Category;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\AbstractSyncHandler;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Pull;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Traits\HandlesRemoteProductsTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class CatalogWebhookReceivedSubscriber extends AbstractWebhookReceivedSubscriber
{
    use HandlesRemoteProductsTrait;

    /**
     * Gets the catalog resource.
     *
     * @param string $resourceId
     *
     * @return Catalog
     * @throws Exception
     */
    public function getResource(string $resourceId) : AbstractModel
    {
        return CatalogsGateway::getNewInstance()->get($resourceId);
    }

    /**
     * Handles the webhook action.
     *
     * @param string $action
     * @param AbstractModel $model
     * @throws Exception
     */
    public function handleAction(string $action, AbstractModel $model)
    {
        if (! $model instanceof Catalog || ! Pull::isEnabled()) {
            return;
        }

        switch ($action) {
            case 'UPDATED':
                $this->handleUpdate($model);
                break;
        }
    }

    /**
     * Handles a catalog update.
     *
     * @param Catalog $catalog
     * @throws Exception
     */
    public function handleUpdate(Catalog $catalog)
    {
        // bail if this catalog is not enabled in the settings
        if (! ArrayHelper::contains(Pull::getEnabledCatalogIds(), $catalog->getRemoteId())) {
            return;
        }

        // ensure the push handler, if enabled, doesn't handle these product updates itself
        Pull::setIsSyncing(true);

        $this->handleRemoteProducts($catalog->getProducts());
        $this->handleRemoteCategories($catalog->getCategories());

        // set the handler back to not syncing
        Pull::setIsSyncing(false);
    }

    /**
     * Handles a list of remote categories.
     *
     * @param array $categories
     * @throws Exception
     */
    protected function handleRemoteCategories(array $categories)
    {
        foreach ($categories as $category) {
            $this->handleRemoteCategory($category);
        }
    }

    /**
     * Handles a remote category.
     *
     * @param Category $category
     * @throws Exception
     */
    protected function handleRemoteCategory(Category $category)
    {
        // TODO: this can be filled in if we support category storage later {@cwiseman 2022-02-11}

        $this->handleRemoteProducts($category->getProducts());
    }

    /**
     * Handles a list of remote products.
     *
     * @param array $products
     * @throws Exception
     */
    protected function handleRemoteProducts(array $products)
    {
        foreach ($products as $remoteProduct) {
            if (! AbstractSyncHandler::shouldSyncProduct($remoteProduct)) {
                continue;
            }

            $this->handleRemoteProduct($remoteProduct);
        }
    }

    /**
     * Handles an existing product.
     *
     * @param Product $existingProduct
     * @param Product $remoteProduct
     *
     * @return Product
     */
    protected function handleExistingProduct(Product $existingProduct, Product $remoteProduct) : Product
    {
        // TODO: existing products are currently ignored by webhooks {@cwiseman 2022-02-11}

        return $remoteProduct;
    }

    /**
     * Gets the product data store.
     *
     * @return ProductDataStore
     */
    protected function getProductDataStore() : ProductDataStore
    {
        return new ProductDataStore('poynt');
    }
}

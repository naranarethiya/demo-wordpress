<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\ProductDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\ProductsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Pull;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class ProductWebhookReceivedSubscriber extends AbstractWebhookReceivedSubscriber
{
    /**
     * Gets the product resource.
     *
     * @param string $resourceId
     *
     * @return Product
     * @throws Exception
     */
    public function getResource(string $resourceId) : AbstractModel
    {
        return ProductsGateway::getNewInstance()->get($resourceId);
    }

    /**
     * {@inheritDoc}
     */
    protected function shouldHandleAction(string $action) : bool
    {
        return 'UPDATED' === $action && Pull::isEnabled();
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
        /* @NOTE at this point the $action type and Pull status have already been checked {@see static::shouldHandleAction()} */
        if (! $model instanceof Product) {
            return;
        }

        $this->handleUpdate($model);
    }

    /**
     * Handles a product update.
     *
     * @param Product $product
     * @throws Exception
     */
    public function handleUpdate(Product $product)
    {
        Pull::setIsSyncing(true);

        $dataStore = ProductDataStore::getNewInstance('poynt');

        // if the product exists in WC, update it
        if ($localProduct = $dataStore->readFromRemoteId($product->getRemoteId())) {
            // ensure the product is passed to the data store with local ID intact
            $product->setId($localProduct->getId());

            $dataStore->save($product);
        }

        Pull::setIsSyncing(false);
    }
}

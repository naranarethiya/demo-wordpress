<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\ProductDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\CatalogsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\ProductsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Pull;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Push;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class ProductUpdatedSubscriber implements SubscriberContract
{
    /** @var CatalogsGateway catalogs gateway instance */
    protected $catalogsGateway;

    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        /** @var ModelEvent $event */
        switch ($event->getAction()) {
            case 'create':
                $this->handleCreate($event->getModel());
                break;
            case 'update':
                $this->handleUpdate($event->getModel());
                break;
            case 'delete':
                $this->handleDelete($event->getModel());
                break;
        }
    }

    /**
     * Determines whether the event should be handled.
     *
     * @param EventContract $event
     * @return bool
     * @throws Exception
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        if (! $event instanceof ModelEvent || $event->getResource() !== 'product') {
            return false;
        }

        // bail if push syncing is disabled, or if a sync is happening in either direction
        if (! Push::isEnabled() || Push::isSyncing() || Pull::isSyncing()) {
            return false;
        }

        return true;
    }

    /**
     * Handles the product created event.
     *
     * @param Product $product
     * @return void
     */
    protected function handleCreate(Product $product)
    {
        if (! $this->shouldUpsertProduct($product)) {
            return;
        }

        Push::setIsSyncing(true);

        try {
            ProductsGateway::getNewInstance()->upsert($product);
            ProductDataStore::getNewInstance('poynt')->save($product);

            foreach (Push::getEnabledCatalogIds() as $catalogId) {
                $this->getCatalogsGateway()->addProducts($catalogId, [$product]);
            }
        } catch (Exception $exception) {
            Push::setIsHealthy(false);
        }

        Push::setIsSyncing(false);
    }

    /**
     * Handles the product updated event.
     *
     * @param Product $product
     * @return void
     */
    protected function handleUpdate(Product $product)
    {
        if (! $this->shouldUpsertProduct($product)) {
            return;
        }

        Push::setIsSyncing(true);

        try {
            $dataStore = ProductDataStore::getNewInstance('poynt');

            if ($product = $dataStore->read($product->getId())) {
                ProductsGateway::getNewInstance()->upsert($product);

                $dataStore->save($product);
            }
        } catch (Exception $exception) {
            Push::setIsHealthy(false);
        }

        Push::setIsSyncing(false);
    }

    /**
     * Handles the product deleted event.
     *
     * @param Product $product
     * @return void
     * @throws Exception
     */
    protected function handleDelete(Product $product)
    {
        if (($product = ProductDataStore::getNewInstance('poynt')->read($product->getId())) && $product->getRemoteId()) {
            foreach (Push::getEnabledCatalogIds() as $catalogId) {
                $this->getCatalogsGateway()->removeProducts($catalogId, [$product]);
            }
        }
    }

    /**
     * Determines whether the given product should be upserted.
     *
     * @param Product $product
     * @return bool
     */
    protected function shouldUpsertProduct(Product $product) : bool
    {
        return 'simple' === $product->getType() && 'publish' === $product->getStatus();
    }

    /**
     * Gets the CatalogsGateway instance.
     *
     * @return CatalogsGateway
     */
    protected function getCatalogsGateway() : CatalogsGateway
    {
        return $this->catalogsGateway ?: $this->catalogsGateway = CatalogsGateway::getNewInstance();
    }
}

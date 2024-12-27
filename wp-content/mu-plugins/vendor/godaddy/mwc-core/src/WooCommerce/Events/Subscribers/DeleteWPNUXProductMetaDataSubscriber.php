<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Models\Products\Product;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use WC_Product;

/**
 * A subscriber to delete the wpnux_id meta key (that indicates a demo product) when a product is updated.
 * Any time a user edits a demo product, we want to assume it’s theirs and no longer a demo.
 */
class DeleteWPNUXProductMetaDataSubscriber implements SubscriberContract
{
    /**
     * Handles product events.
     *
     * @param EventContract $event
     */
    public function handle(EventContract $event) : void
    {
        if ($product = $this->getProductFromEvent($event)) {
            $this->handleProductUpdate($product);
        }
    }

    /**
     * Attempts to get a {@see Product} instance form the given event.
     *
     * @param EventContract $event
     * @return Product|null
     */
    protected function getProductFromEvent(EventContract $event) : ?Product
    {
        if (! $event instanceof ModelEvent || $event->getResource() !== 'product' || $event->getAction() !== 'update') {
            return null;
        }

        $model = $event->getModel();

        return $model instanceof Product ? $model : null;
    }

    /**
     * Deletes the WPNUX metadata from a product when the product is updated.
     *
     * @param Product $product
     * @return void
     */
    protected function handleProductUpdate(Product $product) : void
    {
        $this->deleteMetaDataByProductId((int) $product->getId(), ['wpnux_id']);
    }

    /**
     * Deletes the given meta keys from a WooCommerce product if a product identified with the given ID exists.
     *
     * @param int $id
     * @param non-empty-array<string> $metaKeys
     * @return void
     */
    protected function deleteMetaDataByProductId(int $id, array $metaKeys) : void
    {
        if (! $product = ProductsRepository::get($id)) {
            return;
        }

        $this->deleteProductMetaData($product, $metaKeys);
    }

    /**
     * Deletes the given meta keys from a WooCommerce product.
     *
     * @param WC_Product $product
     * @param non-empty-array<string> $metaKeys
     */
    protected function deleteProductMetaData(WC_Product $product, array $metaKeys) : void
    {
        $deletedMetaKeys = [];

        foreach ($metaKeys as $metaKey) {
            if ($product->meta_exists($metaKey)) {
                $product->delete_meta_data($metaKey);

                $deletedMetaKeys[] = $metaKey;
            }
        }

        if ($deletedMetaKeys) {
            $product->save_meta_data();
        }
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Commands;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\BroadcastSyncedProductsEvent;
use WP_CLI;

/**
 * WP CLI command to dispatch the {@see BroadcastSyncedProductsEvent} for qualifying sites.
 */
class BroadcastSyncedProductsCommand
{
    /** @var string meta key where the Poynt product ID is stored */
    protected const POYNT_PRODUCT_ID_META_KEY = 'mwp_poynt_remoteId';

    /** @var int maximum number of products to query */
    protected const MAX_PRODUCTS_TO_QUERY = 10000;

    /**
     * Executes the command.
     *
     * Usage: `wp mwc poynt broadcast`
     *
     * @param array<string, mixed>|mixed $args
     * @param array<string, mixed>|mixed $assoc_args
     * @return void
     */
    public function __invoke($args, $assoc_args)
    {
        if (! class_exists('WP_CLI')) {
            return;
        }

        if ($this->shouldBroadcast()) {
            $this->broadcastEvent();

            WP_CLI::line('Event successfully dispatched.');
        } else {
            WP_CLI::line('Not eligible for broadcast.');
        }
    }

    /**
     * Determines whether we should broadcast the event.
     *
     * @return bool
     */
    protected function shouldBroadcast() : bool
    {
        try {
            return TypeHelper::bool(Configuration::get('payments.godaddy-payments-payinperson.broadcastSyncedProducts', false), false) &&
                Poynt::hasPoyntSmartTerminalActivated();
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Broadcasts the event.
     *
     * This queries for all synced products and dispatches the {@see BroadcastSyncedProductsEvent} with that data.
     *
     * @return void
     */
    protected function broadcastEvent() : void
    {
        $productIds = TypeHelper::arrayOfIntegers(ProductsRepository::query([
            'return'   => 'ids',
            'meta_key' => static::POYNT_PRODUCT_ID_META_KEY,
            'limit'    => static::MAX_PRODUCTS_TO_QUERY,
        ]));

        Events::broadcast(new BroadcastSyncedProductsEvent($this->getProductData($productIds)));
    }

    /**
     * Gets the product data for the supplied product IDs.
     *
     * @param int[] $productIds
     * @return array<array<string, string|int>>
     */
    protected function getProductData(array $productIds) : array
    {
        if (empty($productIds)) {
            return [];
        }

        update_meta_cache('post', $productIds);

        $productData = [];

        foreach ($productIds as $productId) {
            $productData[] = [
                // intentionally using get_post_meta() calls instead of Product model here for performance
                'wooId'   => $productId,
                'poyntId' => TypeHelper::string(get_post_meta($productId, static::POYNT_PRODUCT_ID_META_KEY, true), ''),
                'sku'     => TypeHelper::string(get_post_meta($productId, '_sku', true), ''),
            ];
        }

        return $productData;
    }
}

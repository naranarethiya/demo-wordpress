<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync;

use Exception;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;

/**
 * The Poynt products push sync handler.
 *
 * Holds configuration details about the push settings, and allows initiating a product sync from WooCommerce to Poynt.
 */
class Push extends AbstractSyncHandler
{
    /** @var string name */
    protected static $name = 'push';

    /**
     * Starts the push sync handler.
     *
     * @return PushSyncJob|null
     * @throws Exception
     */
    public static function start()
    {
        if (static::isSyncing()) {
            return null;
        }

        $args = [
            'status' => 'publish',
            'type'   => 'simple',
            'limit'  => -1, // unlimited, otherwise will default to posts_per_page
            'return' => 'ids',
        ];

        // `wc_get_products()` does not support multiple visibility values, so in order to get products that are either
        // visible in the catalog, search, or both, we have to make 2 calls. We then use the combined unique results.
        // Using array_merge instead of the union operator (+) here to prevent overwriting values.
        $products = array_values(array_unique(array_merge(
            (array) wc_get_products($args + ['visibility' => 'catalog']),
            (array) wc_get_products($args + ['visibility' => 'search'])
        )));

        if (! empty($products)) {
            static::setIsSyncing(true);

            return PushSyncJob::create([
                'owner'      => 'poynt',
                'objectType' => 'product',
                'objectIds'  => $products,
                // TODO: consider adjusting or making the batch size a configuration value in MWC-4148 {IT 2022-02-02}
                'batchSize' => 25,
            ]);
        }

        return null;
    }
}

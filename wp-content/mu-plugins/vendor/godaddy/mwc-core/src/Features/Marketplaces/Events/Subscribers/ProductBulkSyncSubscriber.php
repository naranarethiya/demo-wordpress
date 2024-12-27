<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;

/**
 * Listens to sync events and creates a new {@see PushSyncJob} to send product data to GoDaddy Marketplaces.
 */
class ProductBulkSyncSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        $productIds = $this->getProductIds();

        if (! empty($productIds)) {
            PushSyncJob::create([
                'owner'      => 'marketplaces',
                'objectType' => 'product',
                'objectIds'  => $productIds,
                'batchSize'  => 25,
            ]);
        }
    }

    /**
     * Retrieves the IDs of all published WooCommerce products.
     *
     * @return int[]
     */
    protected function getProductIds() : array
    {
        return (array) wc_get_products([
            'status' => 'publish',
            'limit'  => -1, // retrieve all product IDs
            'return' => 'ids',
        ]);
    }
}

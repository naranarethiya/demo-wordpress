<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ProductBulkSyncEvent;

/**
 * Listens to {@see MerchantProvisionedEvent} events.
 */
class MerchantProvisionedSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     */
    public function handle(EventContract $event)
    {
        // trigger a product bulk sync
        Events::broadcast(new ProductBulkSyncEvent());
    }
}

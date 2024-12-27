<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsInsertedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers\MapInventoryJobHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;

/**
 * The subscriber for {@see ProductsInsertedEvent}s.
 */
class ProductsInsertedEventSubscriber implements SubscriberContract
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        MapInventoryJobHandler::scheduleJobs($event->productAssociations ?? []);
    }

    /**
     * Determines whether the subscriber should handle the event.
     *
     * @param EventContract $event
     *
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return
            $event instanceof ProductsInsertedEvent &&
            ! empty($event->productAssociations) &&
            InventoryIntegration::shouldLoad() &&
            InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\AbstractProductCrudEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductReadEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsListedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductUpdatedDetectionService;

/**
 * Subscriber of {@see ProductReadEvent}, {@see ProductUpdatedEvent}, and {@see ProductsListedEvent}.
 *
 * This subscriber calls the detection service after products have been read from the remote platform. The detection
 * service then determines whether or not those products have changed upstream since the last time we read them.
 */
class MaybeDetectProductChangesSubscriber implements SubscriberContract
{
    /** @var ProductUpdatedDetectionService */
    protected ProductUpdatedDetectionService $productUpdatedDetectionService;

    /**
     * Constructs the subscriber.
     *
     * @param ProductUpdatedDetectionService $productUpdatedDetectionService
     */
    public function __construct(ProductUpdatedDetectionService $productUpdatedDetectionService)
    {
        $this->productUpdatedDetectionService = $productUpdatedDetectionService;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if (! $event instanceof AbstractProductCrudEvent) {
            return;
        }

        $this->productUpdatedDetectionService->detectUpdatesForResources($event->productAssociations);
    }
}

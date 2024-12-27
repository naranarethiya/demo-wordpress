<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiLineItemsPersistentMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiNotesPersistentMappingServiceContract;

class OrderItemsPersistentMappingService
{
    protected MultiNotesPersistentMappingServiceContract $multiNotesMappingService;
    protected MultiLineItemsPersistentMappingServiceContract $multiLineItemsMappingService;

    public function __construct(
        MultiLineItemsPersistentMappingServiceContract $multiLineItemsMappingService,
        MultiNotesPersistentMappingServiceContract $multiNotesMappingService
    ) {
        $this->multiLineItemsMappingService = $multiLineItemsMappingService;
        $this->multiNotesMappingService = $multiNotesMappingService;
    }

    /**
     * Persistently stores the order items remote IDs.
     *
     * @param Order $order
     * @return void
     */
    public function persistOrderItemsRemoteIds(Order $order) : void
    {
        $this->multiLineItemsMappingService->persistRemoteIds($order->getLineItems());
        $this->multiNotesMappingService->persistRemoteIds($order->getNotes());
    }
}

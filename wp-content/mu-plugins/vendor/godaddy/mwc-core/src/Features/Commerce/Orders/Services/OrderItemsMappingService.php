<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Order as CommerceOrder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiLineItemsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiNotesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class OrderItemsMappingService
{
    protected MultiLineItemsMappingServiceContract $multiLineItemsMappingService;

    protected MultiNotesMappingServiceContract $multiNotesMappingService;

    public function __construct(
        MultiLineItemsMappingServiceContract $multiLineItemsMappingService,
        MultiNotesMappingServiceContract $multiNotesMappingService
    ) {
        $this->multiLineItemsMappingService = $multiLineItemsMappingService;
        $this->multiNotesMappingService = $multiNotesMappingService;
    }

    /**
     * Maps remote IDs using necessary multi-item services.
     *
     * @param Order $order
     * @param CommerceOrder $commerceOrder
     * @return void
     */
    public function saveOrderItemsRemoteIds(Order $order, CommerceOrder $commerceOrder) : void
    {
        $this->multiLineItemsMappingService->saveRemoteIds($order->getLineItems(), $commerceOrder->lineItems);
        $this->multiNotesMappingService->saveRemoteIds($order->getNotes(), $commerceOrder->notes);
    }
}

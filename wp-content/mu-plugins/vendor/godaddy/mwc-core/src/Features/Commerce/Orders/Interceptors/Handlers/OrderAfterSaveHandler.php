<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\OrderItemsPersistentMappingService;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

/**
 * A handler for the woocommerce_after_order_object_save hook used to persist remote ID mappings for new orders.
 */
class OrderAfterSaveHandler extends AbstractInterceptorHandler
{
    protected OrderItemsPersistentMappingService $orderItemsPersistentMappingService;

    /**
     * Constructor.
     *
     * @param OrderItemsPersistentMappingService $orderItemsPersistentMappingService
     */
    public function __construct(OrderItemsPersistentMappingService $orderItemsPersistentMappingService)
    {
        $this->orderItemsPersistentMappingService = $orderItemsPersistentMappingService;
    }

    /**
     * Attempts to persist the remote ID mappings for a WooCommerce order.
     *
     * @param mixed ...$args
     */
    public function run(...$args)
    {
        $wooOrder = ArrayHelper::get($args, '0');

        if (! $wooOrder instanceof WC_Order) {
            return;
        }

        if (! $order = $this->adaptOrder($wooOrder)) {
            return;
        }

        $this->persistOrderItemIdMappings($order);
    }

    /**
     * Attempts to convert the given WooCommerce order into an order model.
     *
     * @param WC_Order $wooOrder
     * @return Order|null
     */
    protected function adaptOrder(WC_Order $wooOrder) : ?Order
    {
        try {
            return OrderAdapter::getNewInstance($wooOrder)->convertFromSource();
        } catch (AdapterException $e) {
            return null;
        }
    }

    /**
     * Persists the remote ID mappings for supported order items.
     *
     * @param Order $order
     */
    protected function persistOrderItemIdMappings(Order $order) : void
    {
        $this->orderItemsPersistentMappingService->persistOrderItemsRemoteIds($order);
    }
}

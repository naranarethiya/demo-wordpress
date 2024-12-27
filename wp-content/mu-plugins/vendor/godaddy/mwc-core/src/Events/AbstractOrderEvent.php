<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

/**
 * Abstract order event class.
 *
 * This class and its extensions were kept to keep the backwards compatibility with {@see PoyntOrderPushSubscriber} and
 * {@see OrderUpdatedSubscriber}. For other events data, please refer to {@see Order::toArray()}.
 */
abstract class AbstractOrderEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;
    /** @var WC_Order The order object */
    protected $order;

    /**
     * AbstractOrderEvent constructor.
     */
    public function __construct()
    {
        $this->resource = 'order';
    }

    /**
     * Sets the WooCommerce order object for this event.
     *
     * @since 2.10.0
     *
     * @param WC_Order $order
     * @return self
     */
    public function setWooCommerceOrder(WC_Order $order) : self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Gets the order data for the event.
     *
     * @since 2.10.0
     *
     * TODO: remove this method when a native Order object is available in the Common package {IT 2021-03-24}
     *
     * @param WC_Order $order
     * @return array<string, mixed>
     */
    protected function getOrderData(WC_Order $order) : array
    {
        return [
            'id'                 => $order->get_id(),
            'product_total_cost' => $order->get_meta('_wc_cog_order_total_cost'),
            'currency'           => WooCommerceRepository::getCurrency(),
            'order_status'       => $order->get_status(),
            'payment_method'     => $order->get_payment_method(),
            'source'             => $order->get_created_via(),
            'order_total'        => $order->get_total(),
            'shipping_methods'   => $order->get_shipping_methods(),
        ];
    }

    /**
     * Builds the initial data for the event.
     *
     * @return array<string, mixed>
     */
    protected function buildInitialData() : array
    {
        return $this->order ? $this->getOrderData($this->order) : [];
    }
}

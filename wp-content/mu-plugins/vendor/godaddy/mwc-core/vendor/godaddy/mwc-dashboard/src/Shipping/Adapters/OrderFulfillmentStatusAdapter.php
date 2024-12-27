<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use WC_Order;

class OrderFulfillmentStatusAdapter extends FulfillmentStatusAdapter
{
    /** @var WC_Order WooCommerce order object */
    protected $source;

    public function __construct(WC_Order $order)
    {
        $this->source = $order;
    }

    /**
     * @param Order|null $order
     * @return ?Order
     */
    public function convertFromSource(Order $order = null) : ?Order
    {
        if (null !== $order) {
            $fulfillmentStatus = TypeHelper::string($this->source->get_meta(FulfillmentStatusAdapter::META_KEY), '');

            $order->setFulfillmentStatus($this->getFulfillmentStatusByName($fulfillmentStatus));
        }

        return $order;
    }

    /**
     * @param Order|null $order
     * @return WC_Order
     */
    public function convertToSource(Order $order = null) : WC_Order
    {
        if (is_null($order)) {
            return $this->source;
        }

        $fulfillmentStatus = $order->getFulfillmentStatus();
        $this->source->update_meta_data(FulfillmentStatusAdapter::META_KEY, $fulfillmentStatus ? $fulfillmentStatus->getName() : '');

        return $this->source;
    }
}

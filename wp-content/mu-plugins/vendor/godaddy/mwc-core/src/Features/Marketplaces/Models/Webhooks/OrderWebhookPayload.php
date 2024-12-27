<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Object representing all the information we expect to receive from an order webhook payload.
 */
class OrderWebhookPayload extends AbstractWebhookPayload
{
    /** @var Order|null */
    protected $order;

    /**
     * Gets the order.
     *
     * @return Order|null
     */
    public function getOrder() : ?Order
    {
        return $this->order;
    }

    /**
     * Sets the order.
     *
     * @param Order|null $value
     * @return $this
     */
    public function setOrder(?Order $value) : OrderWebhookPayload
    {
        $this->order = $value;

        return $this;
    }
}

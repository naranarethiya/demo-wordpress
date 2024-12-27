<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\Contracts;

use WC_Order;

/**
 * Represents contract that can set and get an optional WooCommerce order.
 */
interface CanHaveWooCommerceOrderContract
{
    /**
     * Gets a WC_Order instance.
     *
     * @return WC_Order|null
     */
    public function getWooCommerceOrder() : ?WC_Order;

    /**
     * Sets a WC_Order instance.
     *
     * @param WC_Order|null $value
     * @return $this
     */
    public function setWooCommerceOrder(?WC_Order $value);
}

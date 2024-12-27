<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\Traits;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\CanHaveWooCommerceOrderContract;
use WC_Order;

/**
 * Implements {@see CanHaveWooCommerceOrderContract}.
 */
trait CanHaveWooCommerceOrderTrait
{
    protected ?WC_Order $wooCommerceOrder = null;

    /**
     * Gets a WC_Order instance.
     *
     * @return WC_Order|null
     */
    public function getWooCommerceOrder() : ?WC_Order
    {
        return $this->wooCommerceOrder;
    }

    /**
     * Sets a WC_Order instance.
     *
     * @param WC_Order|null $value
     * @return $this
     */
    public function setWooCommerceOrder(?WC_Order $value)
    {
        $this->wooCommerceOrder = $value;

        return $this;
    }
}

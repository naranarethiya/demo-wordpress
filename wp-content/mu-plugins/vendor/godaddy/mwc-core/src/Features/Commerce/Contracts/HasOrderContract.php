<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * A contract for components that can be associated or need access to an {@see Order} instance.
 */
interface HasOrderContract
{
    /**
     * Gets the order for this instance.
     */
    public function getOrder() : ?Order;

    /**
     * Sets the order for this instance.
     *
     * @return $this
     */
    public function setOrder(?Order $value);
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasOrderContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * A trait used to fulfill the {@see HasOrderContract} interface.
 */
trait HasOrderTrait
{
    protected ?Order $order = null;

    /**
     * Gets the order for this instance.
     */
    public function getOrder() : ?Order
    {
        return $this->order;
    }

    /**
     * Sets the order for this instance.
     *
     * @return $this
     */
    public function setOrder(?Order $value)
    {
        $this->order = $value;

        return $this;
    }
}

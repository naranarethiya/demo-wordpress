<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem as CommonLineItem;

/**
 * Marketplaces line item model override.
 */
class LineItem extends CommonLineItem
{
    /** @var string|null */
    protected $orderItemReference;

    /**
     * Gets the order item reference.
     *
     * @return string|null
     */
    public function getOrderItemReference() : ?string
    {
        return $this->orderItemReference;
    }

    /**
     * Sets the order item reference.
     *
     * @param string|null $value
     *
     * @return $this
     */
    public function setOrderItemReference(?string $value) : LineItem
    {
        $this->orderItemReference = $value;

        return $this;
    }
}

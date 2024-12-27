<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;

/**
 * Base contract & individual operations for reservations.
 */
interface ReservationOperationContract
{
    /**
     * Gets the line item.
     *
     * @return LineItem
     */
    public function getLineItem() : LineItem;

    /**
     * Sets the line item for this operation.
     *
     * @param LineItem $value
     *
     * @return $this
     */
    public function setLineItem(LineItem $value) : self;
}

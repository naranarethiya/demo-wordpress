<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReservationOperationContract;

/**
 * Base class for reservations.
 */
abstract class AbstractReservationOperation implements ReservationOperationContract
{
    protected LineItem $lineItem;

    /**
     * @param LineItem $lineItem
     */
    public function __construct(LineItem $lineItem)
    {
        $this->lineItem = $lineItem;
    }

    /**
     * {@inheritDoc}
     */
    public function getLineItem() : LineItem
    {
        return $this->lineItem;
    }

    /**
     * {@inheritDoc}
     */
    public function setLineItem(LineItem $value) : self
    {
        $this->lineItem = $value;

        return $this;
    }
}

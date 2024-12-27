<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateReservationOperationContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class CreateOrUpdateReservationOperation extends AbstractReservationOperation implements CreateOrUpdateReservationOperationContract
{
    protected Order $order;

    /**
     * {@inheritDoc}
     *
     * @param Order $order
     */
    public function __construct(
        LineItem $lineItem,
        Order $order
    ) {
        parent::__construct($lineItem);

        $this->order = $order;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder() : Order
    {
        return $this->order;
    }
}

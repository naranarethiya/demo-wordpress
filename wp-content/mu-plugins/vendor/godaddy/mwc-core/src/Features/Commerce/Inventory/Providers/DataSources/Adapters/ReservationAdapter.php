<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;

class ReservationAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * This method is no-op.
     */
    public function convertFromSource()
    {
        // No-op
    }

    /**
     * Returns a populated Reservation data object based on given line item.
     *
     * @param LineItem|null $lineItem
     * @return Reservation|null
     */
    public function convertToSource(?LineItem $lineItem = null) : ?Reservation
    {
        if (! $lineItem) {
            return null;
        }

        return Reservation::getNewInstance([
            'quantity' => $lineItem->getQuantity(),
        ]);
    }
}

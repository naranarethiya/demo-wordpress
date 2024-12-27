<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertReservationInput;

interface CanCreateOrUpdateReservationsContract
{
    /**
     * Creates or updates the Reservation.
     *
     * @param UpsertReservationInput $input
     *
     * @return Reservation[]
     */
    public function createOrUpdate(UpsertReservationInput $input) : array;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadReservationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;

interface CanReadReservationsContract
{
    /**
     * Reads the reservation.
     *
     * @param ReadReservationInput $input
     *
     * @return Reservation
     */
    public function read(ReadReservationInput $input) : Reservation;
}

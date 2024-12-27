<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;

interface ReservationResponseContract
{
    /**
     * Gets the list of Reservations.
     *
     * @return Reservation[]
     */
    public function getReservations() : array;

    /**
     * Sets the list of Reservations.
     *
     * @param Reservation[] $value
     *
     * @return $this
     */
    public function setReservations(array $value) : self;
}

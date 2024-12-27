<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateReservationResponseContract;

abstract class AbstractReservationResponse implements CreateOrUpdateReservationResponseContract
{
    /** @var Reservation[] */
    protected array $reservations;

    /**
     * @param Reservation[] $reservations
     */
    public function __construct(array $reservations)
    {
        $this->reservations = $reservations;
    }

    /**
     * {@inheritDoc}
     */
    public function getReservations() : array
    {
        return $this->reservations;
    }

    /**
     * {@inheritDoc}
     */
    public function setReservations(array $value) : AbstractReservationResponse
    {
        $this->reservations = $value;

        return $this;
    }
}

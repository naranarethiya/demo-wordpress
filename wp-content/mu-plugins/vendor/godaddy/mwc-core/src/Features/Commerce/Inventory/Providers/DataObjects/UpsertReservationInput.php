<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

class UpsertReservationInput extends AbstractDataObject
{
    public string $storeId;
    public Reservation $reservation;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     *     reservation: Reservation,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

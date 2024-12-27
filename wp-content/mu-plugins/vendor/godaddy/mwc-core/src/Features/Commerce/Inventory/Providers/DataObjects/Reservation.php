<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use DateTime;

class Reservation extends AbstractDataObject
{
    public ?string $inventoryReservationId = null;
    public ?string $inventoryLocationId = null;
    public float $quantity;
    public ?string $productId = null;
    public ?string $type = null;
    public ?string $status = null;
    public ?DateTime $expiresAt = null;

    /**
     * Creates a new reservation.
     *
     * @param array{
     *     inventoryReservationId?: ?string,
     *     inventoryLocationId?: ?string,
     *     quantity: float,
     *     productId?: ?string,
     *     type?: ?string,
     *     status?: ?string,
     *     expiresAt?: ?DateTime,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}

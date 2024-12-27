<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;

/**
 * Adds an adapter class the ability to convert a reservation endpoint response to a {@see Reservation} object.
 */
trait CanConvertReservationResponseTrait
{
    use CanConvertResponseTrait;

    /**
     * Converts {@see ResponseContract} data into a {@see Reservation} object.
     *
     * @param array<string, mixed> $reservationData
     *
     * @return Reservation
     *
     * @throws Exception
     */
    protected function convertReservationResponse(array $reservationData) : Reservation
    {
        $data = ArrayHelper::combine(
            [
                'inventoryReservationId' => ArrayHelper::get($reservationData, 'inventoryReservationId'),
                'type'                   => ArrayHelper::get($reservationData, 'type'),
                'status'                 => ArrayHelper::get($reservationData, 'status'),
                'quantity'               => ArrayHelper::get($reservationData, 'quantity'),
                'productId'              => ArrayHelper::get($reservationData, 'productId'),
            ],
            $this->convertExternalIds($reservationData),
            $this->convertDateTime($reservationData, 'expiresAt'),
            $this->convertDateTime($reservationData, 'createdAt'),
            $this->convertDateTime($reservationData, 'updatedAt'),
        );

        // @phpstan-ignore-next-line
        return new Reservation($data);
    }
}

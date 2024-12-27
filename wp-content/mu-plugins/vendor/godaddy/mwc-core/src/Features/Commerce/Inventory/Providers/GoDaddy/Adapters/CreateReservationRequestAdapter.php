<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;

class CreateReservationRequestAdapter extends AbstractUpsertReservationRequestAdapter
{
    /**
     * {@inheritDoc}
     *
     * @return Reservation[]
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : array
    {
        $reservations = [];

        foreach (ArrayHelper::wrap(ArrayHelper::get($response->getBody(), 'inventoryReservations', [])) as $reservationData) {
            $reservations[] = $this->convertReservationResponse($reservationData);
        }

        return $reservations;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $reservation = $this->input->reservation;

        $externalIds = array_map(function ($externalId) {
            return $externalId->toArray();
        }, $reservation->externalIds);

        $data = [
            'externalIds' => $externalIds,
            'quantity'    => $reservation->quantity,
            'productId'   => $reservation->productId,
        ];

        if (isset($reservation->expiresAt)) {
            ArrayHelper::set($data, 'expiresAt', $reservation->expiresAt
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.v\Z') // "\Z" can be changed to just "p" when we can require PHP 8+
            );
        }

        return $this->getBaseRequest()
            ->setPath('/inventory-reservations')
            ->setMethod('POST')
            ->setBody($data);
    }
}

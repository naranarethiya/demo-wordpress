<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;

class UpdateReservationRequestAdapter extends AbstractUpsertReservationRequestAdapter
{
    /**
     * {@inheritDoc}
     *
     * @return Reservation
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : Reservation
    {
        $data = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryReservation', []));

        return $this->convertReservationResponse($data);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $reservation = $this->input->reservation;

        $data = [];

        if (isset($reservation->inventoryLocationId)) {
            ArrayHelper::set($data, 'locationId', $reservation->inventoryLocationId);
        }

        if (isset($reservation->status)) {
            ArrayHelper::set($data, 'status', $reservation->status);
        }

        return $this->getBaseRequest()
            ->setPath('/inventory-reservations/'.$reservation->inventoryReservationId)
            ->setMethod('PATCH')
            ->setBody($data);
    }
}

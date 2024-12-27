<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadReservationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertReservationResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class ReadReservationRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertReservationResponseTrait;

    protected ReadReservationInput $input;

    /**
     * ReadReservationRequestAdapter constructor.
     *
     * @param ReadReservationInput $input
     */
    public function __construct(ReadReservationInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : Reservation
    {
        $body = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryReservation'));

        return $this->convertReservationResponse($body);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
                      ->setStoreId($this->input->storeId)
                      ->setPath(sprintf('/inventory-reservations/%s', $this->input->inventoryReservationId));
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ReservationsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadReservationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertReservationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\CreateReservationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\ReadReservationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\UpdateReservationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

class ReservationsGateway extends AbstractGateway implements ReservationsGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     *
     * @throws CommerceExceptionContract|Exception
     */
    public function createOrUpdate(UpsertReservationInput $input) : array
    {
        $adapterClass = isset($input->reservation->inventoryReservationId) ? UpdateReservationRequestAdapter::class : CreateReservationRequestAdapter::class;

        /** @var Reservation|Reservation[] $result */
        $result = $this->doAdaptedRequest($adapterClass::getNewInstance($input));

        return ArrayHelper::wrap($result);
    }

    /**
     * {@inheritDoc}
     *
     * @throws CommerceExceptionContract|Exception
     */
    public function read(ReadReservationInput $input) : Reservation
    {
        /** @var Reservation $result */
        $result = $this->doAdaptedRequest(ReadReservationRequestAdapter::getNewInstance($input));

        return $result;
    }
}

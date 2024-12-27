<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateReservationOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadReservationOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateReservationResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadReservationResponseContract;

interface ReservationsServiceContract
{
    /**
     * Create or update a reservation.
     *
     * @param CreateOrUpdateReservationOperationContract $operation
     * @return CreateOrUpdateReservationResponseContract
     * @throws MissingProductRemoteIdException
     * @throws Exception
     */
    public function createOrUpdateReservation(CreateOrUpdateReservationOperationContract $operation) : CreateOrUpdateReservationResponseContract;

    /**
     * Read a reservation.
     *
     * @param ReadReservationOperationContract $operation
     * @return ReadReservationResponseContract
     * @throws CommerceException
     */
    public function readReservation(ReadReservationOperationContract $operation) : ReadReservationResponseContract;
}

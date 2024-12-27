<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

class ReservationMappingService extends AbstractMappingService implements ReservationMappingServiceContract
{
    /**
     * The Reservation Mapping Service constructor.
     */
    public function __construct(ReservationMappingStrategyFactory $reservationMappingStrategyFactory)
    {
        parent::__construct($reservationMappingStrategyFactory);
    }

    /**
     * {@inheritDoc}
     *
     * This is currently a no-op as there is no secondary strategy for reservations.
     */
    protected function getRemoteIdUsingSecondaryStrategy(object $model) : ?string
    {
        return null;
    }
}

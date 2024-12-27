<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

interface HasReservationsContract
{
    /**
     * Returns a reservations gateway.
     *
     * @return ReservationsGatewayContract
     */
    public function reservations() : ReservationsGatewayContract;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

interface HasLevelsContract
{
    /**
     * Returns a levels gateway.
     *
     * @return LevelsGatewayContract
     */
    public function levels() : LevelsGatewayContract;
}

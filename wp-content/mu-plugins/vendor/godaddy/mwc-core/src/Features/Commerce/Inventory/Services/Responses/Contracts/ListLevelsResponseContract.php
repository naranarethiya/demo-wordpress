<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;

interface ListLevelsResponseContract
{
    /**
     * Gets the levels.
     *
     * @return Level[]
     */
    public function getLevels() : array;
}

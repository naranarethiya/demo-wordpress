<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLevelInput;

interface CanCreateOrUpdateLevelsContract
{
    /**
     * Creates or updates the item level.
     *
     * @param UpsertLevelInput $input
     *
     * @return Level
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function createOrUpdate(UpsertLevelInput $input) : Level;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\DeleteLevelInput;

interface CanDeleteLevelsContract
{
    /**
     * Deletes the item level.
     *
     * @param DeleteLevelInput $input
     *
     * @return bool
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function delete(DeleteLevelInput $input) : bool;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLevelsInput;

interface CanListLevelsContract
{
    /**
     * Lists levels.
     *
     * @param ListLevelsInput $input
     *
     * @return Level[]
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function list(ListLevelsInput $input) : array;
}

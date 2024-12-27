<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLocationsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;

interface CanListLocationsContract
{
    /**
     * Lists locations.
     *
     * @param ListLocationsInput $input
     *
     * @return Location[]
     *
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function list(ListLocationsInput $input) : array;
}

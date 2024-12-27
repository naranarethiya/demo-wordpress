<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLocationInput;

interface CanReadLocationsContract
{
    /**
     * Reads a location.
     *
     * @param ReadLocationInput $input
     *
     * @return Location
     *
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function read(ReadLocationInput $input) : Location;
}

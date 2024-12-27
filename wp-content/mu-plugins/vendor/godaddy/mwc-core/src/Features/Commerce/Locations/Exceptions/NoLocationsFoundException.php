<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Exceptions;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;

class NoLocationsFoundException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_NO_LOCATIONS_FOUND_EXCEPTION';
}

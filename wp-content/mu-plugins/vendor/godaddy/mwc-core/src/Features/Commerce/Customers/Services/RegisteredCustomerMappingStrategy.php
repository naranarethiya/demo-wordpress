<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomerMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanMapNumericIdentifierToRemoteIdsTrait;

class RegisteredCustomerMappingStrategy extends AbstractMappingStrategy implements CustomerMappingStrategyContract
{
    use CanMapNumericIdentifierToRemoteIdsTrait;
}

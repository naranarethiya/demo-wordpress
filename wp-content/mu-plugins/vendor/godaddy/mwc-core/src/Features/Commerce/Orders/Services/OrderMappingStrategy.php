<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanMapNumericIdentifierToRemoteIdsTrait;

class OrderMappingStrategy extends AbstractMappingStrategy implements OrderMappingStrategyContract
{
    use CanMapNumericIdentifierToRemoteIdsTrait;
}

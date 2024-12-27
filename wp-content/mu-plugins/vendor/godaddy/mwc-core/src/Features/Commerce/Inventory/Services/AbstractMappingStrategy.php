<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy as CommerceAbstractMappingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanMapNumericIdentifierToRemoteIdsTrait;

abstract class AbstractMappingStrategy extends CommerceAbstractMappingStrategy implements MappingStrategyContract
{
    use CanMapNumericIdentifierToRemoteIdsTrait;
    use CanGetNewInstanceTrait;
}

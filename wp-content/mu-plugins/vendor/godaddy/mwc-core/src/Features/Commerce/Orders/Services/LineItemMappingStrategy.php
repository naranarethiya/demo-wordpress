<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\LineItemMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanAddMappingStrategyTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanMapNumericIdentifierToRemoteIdsTrait;

class LineItemMappingStrategy extends AbstractMappingStrategy implements LineItemMappingStrategyContract
{
    use CanAddMappingStrategyTrait;
    use CanMapNumericIdentifierToRemoteIdsTrait;
}

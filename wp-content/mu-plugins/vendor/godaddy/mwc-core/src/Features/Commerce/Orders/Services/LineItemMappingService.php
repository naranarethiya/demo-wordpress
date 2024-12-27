<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\LineItemMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

class LineItemMappingService extends AbstractMappingService implements LineItemMappingServiceContract
{
    public function __construct(LineItemMappingStrategyFactory $mappingStrategyFactory)
    {
        parent::__construct($mappingStrategyFactory);
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\NoteMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

class NoteMappingService extends AbstractMappingService implements NoteMappingServiceContract
{
    public function __construct(NoteMappingStrategyFactory $mappingStrategyFactory)
    {
        parent::__construct($mappingStrategyFactory);
    }
}

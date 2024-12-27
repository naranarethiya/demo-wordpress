<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

class CustomersMappingService extends AbstractMappingService implements CustomersMappingServiceContract
{
    /**
     * {@inheritDoc}
     */
    public function __construct(CustomerMappingStrategyFactory $customersMappingStrategyFactory)
    {
        parent::__construct($customersMappingStrategyFactory);
    }
}

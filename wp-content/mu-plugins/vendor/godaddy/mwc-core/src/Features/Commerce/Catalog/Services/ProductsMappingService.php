<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

/**
 * Service class to handle mapping of local product IDs to remote product IDs.
 */
class ProductsMappingService extends AbstractMappingService implements ProductsMappingServiceContract
{
    /**
     * {@inheritDoc}
     */
    public function __construct(ProductsMappingStrategyFactory $mappingStrategyFactory)
    {
        parent::__construct($mappingStrategyFactory);
    }
}

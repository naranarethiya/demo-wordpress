<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

/**
 * Service class to handle mapping of local product category IDs to remote category IDs.
 */
class CategoriesMappingService extends AbstractMappingService implements CategoriesMappingServiceContract
{
    /**
     * Constructor.
     *
     * @param CategoriesMappingStrategyFactory $categoriesMappingStrategyFactory
     */
    public function __construct(CategoriesMappingStrategyFactory $categoriesMappingStrategyFactory)
    {
        parent::__construct($categoriesMappingStrategyFactory);
    }
}

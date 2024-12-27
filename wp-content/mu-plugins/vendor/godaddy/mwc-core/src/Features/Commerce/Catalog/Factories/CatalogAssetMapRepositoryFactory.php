<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Factories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Repositories\AbstractCatalogAssetMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CatalogAssetUrlMapRepository;

/**
 * Factory class to determine which {@see AbstractCatalogAssetMapRepository} concrete to use.
 *
 * For now we map using hashed asset URLs via {@see CatalogAssetUrlMapRepository}.
 * In the future we will map using asset UUIDs, once they become available.
 */
class CatalogAssetMapRepositoryFactory
{
    protected CatalogAssetUrlMapRepository $catalogAssetUrlMapRepository;

    public function __construct(CatalogAssetUrlMapRepository $catalogAssetUrlMapRepository)
    {
        $this->catalogAssetUrlMapRepository = $catalogAssetUrlMapRepository;
    }

    /**
     * Gets the catalog asset map repository concrete.
     *
     * @return AbstractCatalogAssetMapRepository
     */
    public function getRepository() : AbstractCatalogAssetMapRepository
    {
        /*
         * In the future this might be a conditional like:
         * return $hasMigratedToUuids ? $this->catalogAssetMapRepository : $this->catalogAssetUrlMapRepository;
         */
        return $this->catalogAssetUrlMapRepository;
    }
}

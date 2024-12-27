<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Factories\CatalogAssetMapRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Abstract catalog asset map repository.
 * {@see CatalogAssetMapRepositoryFactory}.
 */
abstract class AbstractCatalogAssetMapRepository extends AbstractResourceMapRepository
{
    /**
     * Gets the remote ID as it's stored in the database.
     *
     * Once assets have UUIDs, this will return an un-modified UUID.
     * For now it returns a hashed image URL.
     *
     * @param string $remoteId
     * @return string
     */
    public function getRemoteIdForDatabase(string $remoteId) : string
    {
        return $this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId);
    }
}

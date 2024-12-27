<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Repositories\AbstractCatalogAssetMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog\CatalogAssetMapRepositoryServiceProvider;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\HashedRemoteIdMutationStrategy;

/**
 * Maps local attachment IDs to hashed asset URLs.
 *
 * {@see HashedRemoteIdMutationStrategy}
 * {@see CatalogAssetMapRepositoryServiceProvider}
 */
class CatalogAssetUrlMapRepository extends AbstractCatalogAssetMapRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = CommerceResourceTypes::CatalogAssetUrl;
}

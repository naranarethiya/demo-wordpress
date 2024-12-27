<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CatalogAssetUrlMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\ResourceMaps\ResourceMapCachingServiceRouter;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\HashedRemoteIdMutationStrategy;

class CatalogAssetMapRepositoryServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CatalogAssetUrlMapRepository::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        // the map repository binding can be switched later if/when we switch to using UUIDs as the unique identifier.
        //$this->getContainer()->bind(CatalogAssetUrlMapRepository::class, CatalogAssetUuidMapRepository::class);

        $this->getContainer()->singleton(CatalogAssetUrlMapRepository::class, function () {
            /** @var CommerceContextContract $commerceContext */
            $commerceContext = $this->getContainer()->get(CommerceContextContract::class);

            /** @var ResourceMapCachingServiceRouter $resourceMapCachingServiceRouter */
            $resourceMapCachingServiceRouter = $this->getContainer()->get(ResourceMapCachingServiceRouter::class);

            /** @var HashedRemoteIdMutationStrategy $hashedRemoteIdMutationStrategy */
            $hashedRemoteIdMutationStrategy = $this->getContainer()->get(HashedRemoteIdMutationStrategy::class);

            return new CatalogAssetUrlMapRepository($commerceContext, $resourceMapCachingServiceRouter, $hashedRemoteIdMutationStrategy);
        });
    }
}

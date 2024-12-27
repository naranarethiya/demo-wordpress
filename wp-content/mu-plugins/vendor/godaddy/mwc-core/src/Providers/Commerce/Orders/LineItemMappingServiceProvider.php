<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\LineItemMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiLineItemsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiLineItemsPersistentMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\LineItemMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\MultiLineItemsMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\MultiLineItemsPersistentMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LineItemMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\ResourceMaps\ResourceMapCachingServiceRouter;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\LineItemPrefixedRemoteIdMutationStrategy;

class LineItemMappingServiceProvider extends AbstractServiceProvider
{
    /** @var string[] */
    protected array $provides = [
        LineItemMapRepository::class,
        LineItemMappingServiceContract::class,
        MultiLineItemsMappingServiceContract::class,
        MultiLineItemsPersistentMappingServiceContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(LineItemMapRepository::class, function () {
            /** @var CommerceContextContract $commerceContext */
            $commerceContext = $this->getContainer()->get(CommerceContextContract::class);

            /** @var ResourceMapCachingServiceRouter $resourceMapCachingServiceRouter */
            $resourceMapCachingServiceRouter = $this->getContainer()->get(ResourceMapCachingServiceRouter::class);

            /** @var LineItemPrefixedRemoteIdMutationStrategy $remoteIdMutationStrategy */
            $remoteIdMutationStrategy = $this->getContainer()->get(LineItemPrefixedRemoteIdMutationStrategy::class);

            return new LineItemMapRepository($commerceContext, $resourceMapCachingServiceRouter, $remoteIdMutationStrategy);
        });

        $this->getContainer()->bind(LineItemMappingServiceContract::class, LineItemMappingService::class);
        $this->getContainer()->bind(MultiLineItemsMappingServiceContract::class, MultiLineItemsMappingService::class);
        $this->getContainer()->bind(MultiLineItemsPersistentMappingServiceContract::class, MultiLineItemsPersistentMappingService::class);
    }
}

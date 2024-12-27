<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Repositories\OrderMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\ResourceMaps\ResourceMapCachingServiceRouter;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\OrderPrefixedRemoteIdMutationStrategy;

class OrderMapRepositoryServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [OrderMapRepository::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(OrderMapRepository::class, function () {
            /** @var CommerceContextContract $commerceContext */
            $commerceContext = $this->getContainer()->get(CommerceContextContract::class);

            /** @var ResourceMapCachingServiceRouter $resourceMapCachingServiceRouter */
            $resourceMapCachingServiceRouter = $this->getContainer()->get(ResourceMapCachingServiceRouter::class);

            /** @var OrderPrefixedRemoteIdMutationStrategy $remoteIdMutationStrategy */
            $remoteIdMutationStrategy = $this->getContainer()->get(OrderPrefixedRemoteIdMutationStrategy::class);

            return new OrderMapRepository($commerceContext, $resourceMapCachingServiceRouter, $remoteIdMutationStrategy);
        });
    }
}

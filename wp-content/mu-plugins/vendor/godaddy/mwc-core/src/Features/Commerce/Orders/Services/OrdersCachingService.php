<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Exceptions\MissingOrderRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Order;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders\OrderBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyFactoryContract;

class OrdersCachingService extends AbstractCachingService implements OrdersCachingServiceContract
{
    /** {@inheritDoc} */
    protected string $resourceType = 'orders';

    protected OrderBuilder $orderBuilder;

    public function __construct(
        CachingStrategyFactoryContract $cachingStrategy,
        OrderBuilder $orderBuilder
    ) {
        $this->orderBuilder = $orderBuilder;
        parent::__construct($cachingStrategy);
    }

    /**
     * Converts the order data into a {@see Order} Data object.
     *
     * @param array<string, mixed> $resourceArray
     *
     * @return Order
     */
    protected function makeResourceFromArray(array $resourceArray) : object
    {
        return $this->orderBuilder->setData($resourceArray)->build();
    }

    /**
     * Gets the remote ID of the given resource.
     *
     * @param Order $resource
     *
     * @return string
     * @throws MissingOrderRemoteIdException
     */
    protected function getResourceIdentifier(object $resource) : string
    {
        if (! empty($resource->id)) {
            return $resource->id;
        }

        throw MissingOrderRemoteIdException::withDefaultMessage();
    }
}

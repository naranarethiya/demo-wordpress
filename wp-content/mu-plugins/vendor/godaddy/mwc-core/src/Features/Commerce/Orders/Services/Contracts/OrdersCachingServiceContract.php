<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Order;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;

/**
 * Contract for services to handle caching remote {@see Order} objects.
 */
interface OrdersCachingServiceContract extends CachingServiceContract
{
    /**
     * {@inheritDoc}
     * @param callable() : Order $loader
     * @return Order
     */
    public function remember(string $resourceIdentifier, callable $loader) : object;

    /**
     * {@inheritDoc}
     * @return Order|null
     */
    public function get(string $resourceIdentifier) : ?object;

    /**
     * {@inheritDoc}
     * @return Order[]
     */
    public function getMany(array $resourceIdentifiers) : array;

    /**
     * {@inheritDoc}
     * @param Order $resource
     */
    public function set(CanConvertToArrayContract $resource) : void;

    /**
     * {@inheritDoc}
     * @param Order[] $resources
     */
    public function setMany(array $resources) : void;
}

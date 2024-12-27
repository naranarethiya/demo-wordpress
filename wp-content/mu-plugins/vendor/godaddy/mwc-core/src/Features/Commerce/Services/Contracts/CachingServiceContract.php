<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Contract for services to aid in the caching of entities from the platform (e.g. {@see ProductBase} or {@see CustomerBase}).
 */
interface CachingServiceContract
{
    /**
     * Gets an item from the cache if it exists, otherwise executes the loader and caches the result.
     *
     * @param string $resourceIdentifier
     * @param callable $loader
     * @return object
     * @throws CachingStrategyException|CommerceExceptionContract
     */
    public function remember(string $resourceIdentifier, callable $loader) : object;

    /**
     * Gets a resource from the cache by its identifier.
     *
     * @param string $resourceIdentifier
     * @return object|null
     */
    public function get(string $resourceIdentifier) : ?object;

    /**
     * Gets multiple resources from the cache.
     *
     * @param string[] $resourceIdentifiers
     * @return object[]
     */
    public function getMany(array $resourceIdentifiers) : array;

    /**
     * Adds a resource to the cache.
     *
     * @param CanConvertToArrayContract $resource
     * @return void
     * @throws CachingStrategyException|CommerceExceptionContract
     */
    public function set(CanConvertToArrayContract $resource) : void;

    /**
     * Adds multiple resources to the cache.
     *
     * @param CanConvertToArrayContract[] $resources
     * @return void
     * @throws CachingStrategyException|CommerceExceptionContract
     */
    public function setMany(array $resources) : void;

    /**
     * Removes a resource from the cache.
     *
     * @param string $resourceIdentifier
     * @return void
     * @throws CachingStrategyException
     */
    public function remove(string $resourceIdentifier) : void;
}

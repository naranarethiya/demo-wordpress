<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\ResourceMaps;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions;

/**
 * Resource Map Caching Service Router.
 *
 * A class that helps route cache lookups to the correct service class, depending on whether we need to get a record
 * by its local ID ({@see ResourceMapLocalIdsCachingService}) or remote ID ({@see ResourceMapCommerceIdsCachingService}).
 */
class ResourceMapCachingServiceRouter
{
    protected ResourceMapLocalIdsCachingService $resourceMapLocalIdsCachingService;
    protected ResourceMapCommerceIdsCachingService $resourceMapCommerceIdsCachingService;

    public function __construct(ResourceMapLocalIdsCachingService $resourceMapLocalIdsCachingService, ResourceMapCommerceIdsCachingService $resourceMapCommerceIdsCachingService)
    {
        $this->resourceMapLocalIdsCachingService = $resourceMapLocalIdsCachingService;
        $this->resourceMapCommerceIdsCachingService = $resourceMapCommerceIdsCachingService;
    }

    /**
     * Gets many items from the cache by their local IDs.
     *
     * @param int[] $localIds
     * @return ResourceMap[]
     */
    public function getManyByLocalIds(array $localIds) : array
    {
        // convert to strings to be compatible with caching service
        $localIds = array_map('strval', $localIds);

        return TypeHelper::arrayOf(
            $this->resourceMapLocalIdsCachingService->getMany($localIds),
            ResourceMap::class
        );
    }

    /**
     * Gets many items from the cache by their commerce IDs.
     *
     * @param string[] $commerceIds
     * @return ResourceMap[]
     */
    public function getManyByCommerceIds(array $commerceIds) : array
    {
        return TypeHelper::arrayOf(
            $this->resourceMapCommerceIdsCachingService->getMany($commerceIds),
            ResourceMap::class
        );
    }

    /**
     * Caches a single resource.
     *
     * @param ResourceMap $resourceMap
     * @return void
     * @throws Exceptions\CachingStrategyException|CommerceExceptionContract
     */
    public function set(ResourceMap $resourceMap) : void
    {
        $this->resourceMapLocalIdsCachingService->set($resourceMap);
        $this->resourceMapCommerceIdsCachingService->set($resourceMap);
    }

    /**
     * Caches multiple resources.
     *
     * @param ResourceMap[] $resourceMaps
     * @return void
     * @throws CommerceExceptionContract|Exceptions\CachingStrategyException
     */
    public function setMany(array $resourceMaps) : void
    {
        $this->resourceMapLocalIdsCachingService->setMany($resourceMaps);
        $this->resourceMapCommerceIdsCachingService->setMany($resourceMaps);
    }

    /**
     * Gets an item from the cache by local ID if it exists, otherwise executes the loader and caches the result.
     *
     * @param int $localId
     * @param callable(int $localId): ResourceMap $loader
     * @return ResourceMap
     * @throws Exceptions\CachingStrategyException|CommerceExceptionContract
     */
    public function rememberByLocalId(int $localId, callable $loader) : object
    {
        // Explicitly cast as a string as TypeHelper::string() checks is_string().
        return $this->resourceMapLocalIdsCachingService->remember((string) $localId, $loader);
    }

    /**
     * Gets an item from the cache by commerce ID if it exists, otherwise executes the loader and caches the result.
     *
     * @param string $commerceId
     * @param callable(string $commerceId): ResourceMap $loader
     * @return ResourceMap
     * @throws CommerceExceptionContract
     * @throws Exceptions\CachingStrategyException
     */
    public function rememberByCommerceId(string $commerceId, callable $loader) : object
    {
        return $this->resourceMapCommerceIdsCachingService->remember($commerceId, $loader);
    }

    /**
     * Removes a resource from the cache.
     *
     * @param ResourceMap $resourceMap
     * @return void
     * @throws Exceptions\CachingStrategyException
     */
    public function remove(ResourceMap $resourceMap) : void
    {
        // Explicitly cast as a string as TypeHelper::string() checks is_string().
        $this->resourceMapLocalIdsCachingService->remove((string) $resourceMap->localId);
        $this->resourceMapCommerceIdsCachingService->remove($resourceMap->commerceId);
    }

    /**
     * Sets the resource type.
     *
     * @param string $resourceType
     * @return $this
     */
    public function setResourceType(string $resourceType) : ResourceMapCachingServiceRouter
    {
        $this->resourceMapLocalIdsCachingService->setResourceType($resourceType);
        $this->resourceMapCommerceIdsCachingService->setResourceType($resourceType);

        return $this;
    }
}

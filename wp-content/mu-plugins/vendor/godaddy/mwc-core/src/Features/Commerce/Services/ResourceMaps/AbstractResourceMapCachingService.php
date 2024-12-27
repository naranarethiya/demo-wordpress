<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\ResourceMaps;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Factories\WpCacheCachingStrategyFactory;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Abstract service class for caching {@see AbstractResourceMapRepository} database queries.
 */
abstract class AbstractResourceMapCachingService extends AbstractCachingService
{
    protected CommerceContextContract $commerceContext;

    public function __construct(CommerceContextContract $commerceContext, WpCacheCachingStrategyFactory $cachingStrategyFactory)
    {
        $this->commerceContext = $commerceContext;

        parent::__construct($cachingStrategyFactory);
    }

    /**
     * {@inheritDoc}
     * @return object&ResourceMap
     */
    protected function makeResourceFromArray(array $resourceArray) : object
    {
        return new ResourceMap(
            TypeHelper::int(ArrayHelper::get($resourceArray, 'id'), 0),
            TypeHelper::string(ArrayHelper::get($resourceArray, 'commerceId'), ''),
            TypeHelper::int(ArrayHelper::get($resourceArray, 'localId'), 0)
        );
    }

    /**
     * Sets the resource type.
     *
     * @param string $resourceType
     * @return $this
     */
    public function setResourceType(string $resourceType) : AbstractResourceMapCachingService
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    /**
     * This is overridden to adjust the return type.
     *
     * {@inheritDoc}
     * @return ResourceMap|null
     */
    protected function convertJsonResource(string $jsonResource) : ?object
    {
        /** @var ResourceMap|null $resource */
        $resource = parent::convertJsonResource($jsonResource);

        return $resource;
    }

    /**
     * Overridden to enforce the return type.
     *
     * {@inheritDoc}
     * @return ResourceMap
     */
    public function remember(string $resourceIdentifier, callable $loader) : object
    {
        $resourceMap = parent::remember($resourceIdentifier, $loader);

        if ($resourceMap instanceof ResourceMap) {
            return $resourceMap;
        }

        throw new CachingStrategyException('Unexpected resource map type returned.');
    }
}

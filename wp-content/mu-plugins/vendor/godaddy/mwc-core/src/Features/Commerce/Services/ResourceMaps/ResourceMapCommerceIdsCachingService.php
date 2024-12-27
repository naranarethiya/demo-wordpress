<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\ResourceMaps;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;
use InvalidArgumentException;

/**
 * Caching service for {@see AbstractResourceMapRepository}, using commerce IDs as the resource identifier.
 * (This allows look-ups by commerce ID.).
 */
class ResourceMapCommerceIdsCachingService extends AbstractResourceMapCachingService
{
    /**
     * {@inheritDoc}
     * @param ResourceMap&object $resource
     *
     * @throws InvalidArgumentException
     */
    protected function getResourceIdentifier(object $resource) : string
    {
        if (empty($resource->commerceId)) {
            throw new InvalidArgumentException('Resource must have a commerce ID to be cached.');
        }

        return $resource->commerceId;
    }

    /**
     * {@inheritDoc}
     */
    protected function getCacheGroup() : string
    {
        // "godaddy-commerce-{$resourceType}-commerce-{$contextId}
        return parent::getCacheGroup().'-commerce-'.$this->commerceContext->getId();
    }
}

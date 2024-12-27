<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\ResourceMaps;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Caching service for {@see AbstractResourceMapRepository}, using local IDs as the resource identifier.
 * (This allows look-ups by local ID.).
 */
class ResourceMapLocalIdsCachingService extends AbstractResourceMapCachingService
{
    /**
     * {@inheritDoc}
     * @param ResourceMap&object $resource
     */
    protected function getResourceIdentifier(object $resource) : string
    {
        return (string) $resource->localId;
    }

    /**
     * {@inheritDoc}
     */
    protected function getCacheGroup() : string
    {
        // "godaddy-commerce-{$resourceType}-local-{$contextId}
        return parent::getCacheGroup().'-local-'.$this->commerceContext->getId();
    }
}

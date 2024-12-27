<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Helpers\Contracts\ListRemoteResourcesCachingHelperContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;

/**
 * Abstract cache helper for listing remote resources.
 */
abstract class AbstractListRemoteResourcesCachingHelper implements ListRemoteResourcesCachingHelperContract
{
    /** @var CachingServiceContract caching service */
    protected CachingServiceContract $cachingService;

    /**
     * Constructor.
     *
     * @param CachingServiceContract $cachingService
     */
    public function __construct(CachingServiceContract $cachingService)
    {
        $this->cachingService = $cachingService;
    }

    /**
     * Gets cached resources from an operation.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return array|AbstractDataObject[]
     */
    public function getCachedResourcesFromOperation(ListRemoteResourcesOperationContract $operation) : array
    {
        $remoteIds = $operation->getIds();

        if (empty($remoteIds) || ! ArrayHelper::accessible($remoteIds)) {
            return [];
        }

        /** @var AbstractDataObject[] $result */
        $result = $this->cachingService->getMany($remoteIds);

        return $result;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Helpers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Helper to aid in determining cache status for list operations. The primary purpose of the contract is to determine
 * whether the operation is already 100% cached, thus allowing us to skip an API request completely.
 */
interface ListRemoteResourcesCachingHelperContract
{
    /**
     * Determines if an operation can be cached.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return bool
     */
    public function canCacheOperation(ListRemoteResourcesOperationContract $operation) : bool;

    /**
     * Gets cached resources from an operation.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return AbstractDataObject[]
     */
    public function getCachedResourcesFromOperation(ListRemoteResourcesOperationContract $operation) : array;

    /**
     * Determines if the operation has been fully cached.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @param object[] $cachedResources
     * @return bool
     */
    public function isOperationFullyCached(ListRemoteResourcesOperationContract $operation, array $cachedResources) : bool;
}

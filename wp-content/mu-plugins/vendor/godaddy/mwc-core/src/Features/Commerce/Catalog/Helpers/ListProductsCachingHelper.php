<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\Contracts\ListProductsCachingHelperContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;

/**
 * List products caching service.
 */
class ListProductsCachingHelper extends AbstractListRemoteResourcesCachingHelper implements ListProductsCachingHelperContract
{
    /**
     * Constructor.
     *
     * @param ProductsCachingServiceContract $cachingService
     */
    public function __construct(ProductsCachingServiceContract $cachingService)
    {
        parent::__construct($cachingService);
    }

    /**
     * Determines if the operation can be cached. We can only cache operations where we're querying by `ids` only and no other params.
     *
     * @param ListProductsOperationContract $operation
     * @return bool
     */
    public function canCacheOperation(ListRemoteResourcesOperationContract $operation) : bool
    {
        // @NOTE the toArray method in this context will remove empty key-value pairs from the array
        $queryArgs = $operation->toArray();

        return ! empty($queryArgs['ids']) && empty(ArrayHelper::except($queryArgs, ['ids', 'pageSize']));
    }

    /**
     * Determines if the operation is fully cached.
     *
     * @param ListProductsOperationContract $operation
     * @param ProductBase[] $cachedResources
     * @return bool
     */
    public function isOperationFullyCached(ListRemoteResourcesOperationContract $operation, array $cachedResources) : bool
    {
        if (! $remoteIds = $operation->getIds()) {
            return false;
        }

        $cachedProductIds = array_column($cachedResources, 'productId');

        // we only need to proceed with query execution if some requested IDs are not in cache
        return count(array_diff($remoteIds, $cachedProductIds)) === 0;
    }
}

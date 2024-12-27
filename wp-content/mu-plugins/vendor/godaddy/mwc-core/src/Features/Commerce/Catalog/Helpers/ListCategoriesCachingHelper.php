<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListCategoriesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;

/**
 * List categories caching helper.
 *
 * @method Category[] getCachedResourcesFromOperation(ListCategoriesOperationContract $operation)
 */
class ListCategoriesCachingHelper extends AbstractListRemoteResourcesCachingHelper
{
    /**
     * Constructor.
     *
     * @param CategoriesCachingServiceContract $cachingService
     */
    public function __construct(CategoriesCachingServiceContract $cachingService)
    {
        parent::__construct($cachingService);
    }

    /**
     * Determines if the operation can be cached. We can only cache operations where we're querying by `ids` only and
     * no other params (except `pageSize`).
     *
     * @param ListCategoriesOperationContract $operation
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
     * @param ListCategoriesOperationContract $operation
     * @param Category[] $cachedResources
     * @return bool
     */
    public function isOperationFullyCached(ListRemoteResourcesOperationContract $operation, array $cachedResources) : bool
    {
        if (! $remoteIds = $operation->getIds()) {
            return false;
        }

        $cachedProductIds = array_column($cachedResources, 'categoryId');

        // we only need to proceed with query execution if some requested IDs are not in cache
        return count(array_diff($remoteIds, $cachedProductIds)) === 0;
    }
}

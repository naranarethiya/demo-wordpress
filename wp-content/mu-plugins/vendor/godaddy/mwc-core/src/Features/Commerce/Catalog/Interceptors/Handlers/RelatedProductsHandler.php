<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\BatchRequestHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanDetermineShouldReadProductsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Handler for the related products interceptor. This is responsible for pre-warming the cache for related product
 * IDs to prevent N+1 issues.
 */
class RelatedProductsHandler extends AbstractInterceptorHandler
{
    use CanDetermineShouldReadProductsTrait;

    protected ListProductsServiceContract $listProductsService;

    /**
     * Constructor.
     *
     * @param ListProductsServiceContract $listProductsService
     */
    public function __construct(ListProductsServiceContract $listProductsService)
    {
        $this->listProductsService = $listProductsService;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        $relatedProducts = TypeHelper::arrayOfIntegers($args[0] ?? [], false);

        try {
            if (! empty($relatedProducts) && $this->shouldReadProducts()) {
                // @TODO replace the below logic with BatchListProductsByLocalIdService in MWC-14458 {agibson 2023-10-23}
                $maximumProducts = $this->getMaximumRelatedProducts();

                $localProductIds = array_slice($relatedProducts, 0, $maximumProducts);

                $this->prefetchRelatedProducts($localProductIds);

                // wc_get_related_products may shuffle the returned array after this filter. If it's bigger than the max we can request, then return that.
                if (count($relatedProducts) > $maximumProducts) {
                    return $localProductIds;
                }
            }
        } catch(MissingRemoteIdsAfterLocalIdConversionException $e) {
            // not an exception we need to report
        } catch(CommerceExceptionContract|Exception $e) {
            // catch and report exceptions in hook callbacks
            SentryException::getNewInstance('Failed to pre-fetch related products.', $e);
        }

        return $relatedProducts;
    }

    /**
     * Gets the maximum number of related products we should query in one API request.
     *
     * @return positive-int
     */
    protected function getMaximumRelatedProducts() : int
    {
        return BatchRequestHelper::getMaxIdsPerRequest();
    }

    /**
     * Prefetches the given products from the commerce platform so they are cached for later individual access.
     *
     * @param int[] $localProductIds
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function prefetchRelatedProducts(array $localProductIds) : void
    {
        $this->listProductsService->list(ListProductsOperation::getNewInstance()->setLocalIds($localProductIds));
    }
}

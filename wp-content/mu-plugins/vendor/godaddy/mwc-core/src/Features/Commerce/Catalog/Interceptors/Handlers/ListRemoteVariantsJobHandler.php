<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\BatchRequestHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ListRemoteVariantsJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

/**
 * List variant products, which inserts products that don't exist locally.
 * Companion to {@see RemoteProductsPollingProcessor}.
 */
class ListRemoteVariantsJobHandler extends AbstractInterceptorHandler
{
    protected ProductsServiceContract $productsService;

    public function __construct(ProductsServiceContract $productsService)
    {
        $this->productsService = $productsService;
    }

    /**
     * Schedules a new background job to list variants, if the products in the provided `$listProductsResponse` have any variants included in them.
     *
     * @param ListProductsResponseContract $listProductsResponse
     * @return void
     */
    public static function scheduleIfHasVariants(ListProductsResponseContract $listProductsResponse) : void
    {
        $chunkedVariantProductIds = static::getChunkedVariantProductIds($listProductsResponse);

        foreach ($chunkedVariantProductIds as $variantProductIds) {
            try {
                static::scheduleJob($variantProductIds);
            } catch (InvalidScheduleException $e) {
                SentryException::getNewInstance('Could not schedule job to list variants.', $e);
            }
        }
    }

    /**
     * Gets an array of chunked variant product IDs from the supplied response.
     *
     * @param ListProductsResponseContract $listProductsResponse
     * @return string[][]
     */
    protected static function getChunkedVariantProductIds(ListProductsResponseContract $listProductsResponse) : array
    {
        $variantProductIds = ArrayHelper::flatten(array_map(
            static function (ProductAssociation $productAssociation) : array {
                return TypeHelper::arrayOfStrings($productAssociation->remoteResource->variants);
            },
            $listProductsResponse->getProducts()
        ));

        return static::getChunkedIds($variantProductIds, BatchRequestHelper::getMaxIdsPerRequest());
    }

    /**
     * Schedules the variant job with the supplied product IDs.
     *
     * @param string[] $variantProductIds
     * @return void
     * @throws InvalidScheduleException
     */
    protected static function scheduleJob(array $variantProductIds) : void
    {
        Schedule::singleAction()->setName(ListRemoteVariantsJobInterceptor::JOB_NAME)
            ->setArguments($variantProductIds)
            ->setScheduleAt(new DateTime())
            ->schedule();
    }

    /**
     * List variant products with the given IDs, which inserts products that don't exist locally.
     * {@see RemoteProductsPollingProcessor}.
     *
     * @note This method is public so that external code can call it to list variants inline with a product read.
     *
     * @param string[] $variantProductIds
     */
    public function processVariants(array $variantProductIds) : void
    {
        try {
            $this->productsService->listProducts(
                ListProductsOperation::getNewInstance()
                    ->setIds($variantProductIds)
                    ->setPageSize(count($variantProductIds))
            );
        } catch(Exception|CommerceExceptionContract $e) {
            // @TODO in the future perhaps we want to re-schedule the list job, but with back-off {agibson 2023-06-15}
            SentryException::getNewInstance('Failed to list variant products.', $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        list($variantProductIds) = array_pad($args, 1, null);

        $this->processVariants(
            TypeHelper::arrayOfStrings($variantProductIds)
        );
    }

    /**
     * @param string[] $variantProductIds
     * @param positive-int $numberPerChunk
     *
     * @return string[][]
     */
    protected static function getChunkedIds(array $variantProductIds, int $numberPerChunk) : array
    {
        return array_chunk(array_unique($variantProductIds), $numberPerChunk);
    }
}

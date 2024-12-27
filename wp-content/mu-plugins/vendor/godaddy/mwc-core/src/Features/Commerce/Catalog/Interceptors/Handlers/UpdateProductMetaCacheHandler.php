<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostMetaAggregator;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;

/**
 * Update product meta cache handler.
 *
 * This handler will update the local product post meta cache with remote product metadata.
 */
class UpdateProductMetaCacheHandler extends AbstractInterceptorHandler
{
    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productService;

    protected SummariesServiceContract $summariesService;

    protected ProductPostMetaAggregator $productPostMetaAggregator;

    public function __construct(
        ProductsServiceContract $productService,
        SummariesServiceContract $summariesService,
        ProductPostMetaAggregator $productPostMetaAggregator
    ) {
        $this->productService = $productService;
        $this->summariesService = $summariesService;
        $this->productPostMetaAggregator = $productPostMetaAggregator;
    }

    /**
     * Updates local product meta cache with remote product meta.
     *
     * @param array<int, mixed> $args hook arguments
     * @return array<int, array<string, array<?string>>> cache data
     */
    public function run(...$args) : array
    {
        /** @var array<int, array<string, array<?string>>> $cache */
        $cache = TypeHelper::array($args[0] ?? [], []);
        $objectIds = TypeHelper::arrayOfIntegers($args[1] ?? []);
        $metaType = TypeHelper::string($args[2] ?? '', '');

        if (! $this->shouldUpdate($metaType, $objectIds)) {
            return $cache;
        }

        try {
            $listProducts = $this->productService->listProducts(ListProductsOperation::seed(['localIds' => $objectIds]));
        } catch (MissingRemoteIdsAfterLocalIdConversionException $exception) {
            // we don't need to report this exception to Sentry
            return $cache;
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            return $cache;
        }

        return $this->update($cache, $listProducts->getProducts());
    }

    /**
     * Determines whether the cache should be updated for a given set.
     *
     * @param string $metaType
     * @param int[] $objectIds local product IDs
     * @return bool
     */
    protected function shouldUpdate(string $metaType, array $objectIds) : bool
    {
        return 'post' === $metaType && ! empty($objectIds) && CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Updates the cache metadata related to products.
     *
     * @param array<int, array<string, array<?string>>> $cache
     * @param ProductAssociation[] $productAssociations
     * @return array<int, array<string, array<?string>>> the updated cache
     */
    protected function update(array $cache, array $productAssociations) : array
    {
        foreach ($productAssociations as $productAssociation) {
            // merges the local product cached metadata with remote metadata from catalog
            $localMeta = $cache[$productAssociation->localId] ?? [];

            $cache[$productAssociation->localId] = $this->productPostMetaAggregator->aggregate($localMeta, $productAssociation->remoteResource);
        }

        return $cache;
    }
}

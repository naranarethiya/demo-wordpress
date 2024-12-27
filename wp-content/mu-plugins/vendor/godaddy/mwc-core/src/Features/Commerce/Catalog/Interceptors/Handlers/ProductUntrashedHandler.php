<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\CreateOrUpdateProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use WC_Product;

/**
 * Handler to respond to a trashed product being "untrashed".
 * When we do this we need to update the product in the platform to change {@see ProductBase::$active} to `true`.
 */
class ProductUntrashedHandler extends AbstractInterceptorHandler
{
    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    /** @var ProductsMappingServiceContract */
    protected ProductsMappingServiceContract $productsMappingService;

    /**
     * Constructor.
     */
    public function __construct(ProductsServiceContract $productsService, ProductsMappingServiceContract $productsMappingService)
    {
        $this->productsService = $productsService;
        $this->productsMappingService = $productsMappingService;
    }

    /**
     * Handler runs on the `untrashed_post` action.
     *
     * This hook fires for all post types, so first we have to validate if the provided post id is a valid product.
     * {@link https://developer.wordpress.org/reference/hooks/untrashed_post/}
     */
    public function run(...$args)
    {
        /** @var int $postId */
        $postId = $args[0] ?? null;

        /** @var WC_Product|null $sourceProduct */
        $sourceProduct = ProductsRepository::get($postId);

        if ($sourceProduct) {
            try {
                $nativeProduct = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();
                $nativeProduct->setStatus('publish');
                $remoteId = $this->productsMappingService->getRemoteId($nativeProduct);

                // @NOTE if we don't have a remote ID that means the product hasn't been written to the platform yet
                if ($remoteId) {
                    $operation = CreateOrUpdateProductOperation::fromProduct($nativeProduct);
                    $this->productsService->updateProduct($operation, $remoteId);
                }
            } catch(Exception $e) {
                SentryException::getNewInstance('Failed to handle untrashed product: '.$e->getMessage(), $e);
            }
        }
    }
}

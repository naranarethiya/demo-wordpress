<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\LocalProductDeletedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\CreateOrUpdateProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ChannelIds;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WP_Post;

/**
 * Handler for {@see LocalProductDeletedInterceptor}, to respond to product deleted actions.
 */
class LocalProductDeletedHandler extends AbstractInterceptorHandler
{
    /** @var ProductMapRepository Products map repository for access to mapping */
    protected ProductMapRepository $productMapRepository;

    /** @var ProductsServiceContract Products service for access to request adapters */
    protected ProductsServiceContract $productsService;

    /**
     * Constructor.
     *
     * @param ProductMapRepository $productMapRepository
     * @param ProductsServiceContract $productsService
     */
    public function __construct(ProductMapRepository $productMapRepository, ProductsServiceContract $productsService)
    {
        $this->productMapRepository = $productMapRepository;
        $this->productsService = $productsService;
    }

    /**
     * Executes the callback for `delete_post` actions.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        /** @var WP_Post|null $productPost */
        $productPost = $args[1] ?? null;

        if ($productPost && $this->shouldHandle($productPost)) {
            try {
                $this->handleUpdatingDeletedProductInPlatform($productPost);

                $this->handleDeletedProduct($productPost);
            } catch(Exception $e) {
                SentryException::getNewInstance('Failed to handle deleted product', $e);
            }
        }
    }

    /**
     * Determines whether we should handle the deletion event.
     *
     * @param WP_Post|mixed $productPost
     * @return bool
     */
    protected function shouldHandle($productPost) : bool
    {
        $productPostTypes = [
            CatalogIntegration::PRODUCT_POST_TYPE,
            CatalogIntegration::PRODUCT_VARIATION_POST_TYPE,
        ];

        return $productPost instanceof WP_Post && in_array($productPost->post_type, $productPostTypes, true);
    }

    /**
     * Handles instances where a product has been deleted.
     *
     * @param WP_Post $wpPost
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function handleDeletedProduct(WP_Post $wpPost) : void
    {
        $this->productMapRepository->deleteByLocalId(TypeHelper::int($wpPost->ID, 0));
    }

    /**
     * Handles updating the Woo-deleted product at the platform level.
     *
     * @param WP_Post $productPost
     * @return void
     */
    protected function handleUpdatingDeletedProductInPlatform(WP_Post $productPost) : void
    {
        try {
            $remoteId = $this->productMapRepository->getRemoteId($productPost->ID);
            $sourceProduct = $remoteId ? ProductsRepository::get($productPost->ID) : null;

            if ($sourceProduct && $remoteId) {
                $nativeProduct = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();
                $operation = $this->getUpdateProductOperationForLocalDeletion($nativeProduct);

                $this->productsService->updateProduct($operation, $remoteId);
            }
        } catch (Exception $exception) {
            SentryException::getNewInstance(sprintf('An error occurred trying to update a remote record for a product: %s', $exception->getMessage()), $exception);
        }
    }

    /**
     * Builds and returns the CreateOrUpdateProductOperation object.
     *
     * @param Product $nativeProduct
     * @return CreateOrUpdateProductOperation
     */
    protected function getUpdateProductOperationForLocalDeletion(Product $nativeProduct) : CreateOrUpdateProductOperation
    {
        // Setting the status to "deleted" will cause the product's `active` property to be set to false {@see ProductBase}.
        $nativeProduct->setStatus('deleted');

        $operation = CreateOrUpdateProductOperation::fromProduct($nativeProduct);

        $operation->setChannelIds(ChannelIds::getNewInstance([
            'remove' => [Commerce::getChannelId()],
        ]));

        return $operation;
    }
}

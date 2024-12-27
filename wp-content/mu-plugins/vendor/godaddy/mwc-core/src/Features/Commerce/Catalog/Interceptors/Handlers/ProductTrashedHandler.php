<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\CreateOrUpdateProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WP_Post;

/**
 * Handler to respond to a product being moved to the trash.
 * When this happens we update the product in the platform to set {@see ProductBase::$active} to `false`.
 */
class ProductTrashedHandler extends AbstractInterceptorHandler
{
    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    /**
     * Constructor.
     */
    public function __construct(ProductsServiceContract $productsService)
    {
        $this->productsService = $productsService;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        /** @var WP_Post|null $productPost */
        $productPost = $args[1] ?? null;

        if ($productPost) {
            try {
                $this->productsService->updateProductFromWpPost($productPost, fn (Product $nativeProduct) => $this->getUpdateProductOperationForLocalDeletion($nativeProduct));
            } catch(MissingProductRemoteIdException $e) {
                // indicates that the product was never written to the platform -- we do not need to report this
            } catch(Exception $e) {
                SentryException::getNewInstance('Failed to handle deleted product', $e);
            }
        }
    }

    protected function getUpdateProductOperationForLocalDeletion(Product $product) : CreateOrUpdateProductOperation
    {
        return CreateOrUpdateProductOperation::fromProduct($product);
    }
}

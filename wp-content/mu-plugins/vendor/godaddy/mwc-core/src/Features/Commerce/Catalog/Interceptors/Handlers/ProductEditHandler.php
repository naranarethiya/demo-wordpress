<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Content\Context\Screen;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\BatchRequestHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\CheckForDeletedProductHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\VariantUpdateDeleteHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductEditInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListCategoriesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ListCategoriesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanReconcileRemoteProductsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

/**
 * Callback for {@see ProductEditInterceptor}.
 *
 * This is used to reconcile local and remote variants for a product when it is being edited.
 *
 * @see ProductVariationReadHandler for the frontend equivalent
 */
class ProductEditHandler extends AbstractInterceptorHandler
{
    use CanReconcileRemoteProductsTrait;

    /** @var VariantUpdateDeleteHelper */
    protected VariantUpdateDeleteHelper $variantUpdateDeleteHelper;

    /** @var CheckForDeletedProductHelper */
    protected CheckForDeletedProductHelper $checkForDeletedProductHelper;

    /** @var ListCategoriesService */
    protected ListCategoriesService $listCategoriesService;

    /**
     * Constructor.
     *
     * @param VariantUpdateDeleteHelper $variantUpdateDeleteHelper
     * @param CheckForDeletedProductHelper $checkForDeletedProductHelper
     * @param ListCategoriesService $listCategoriesService
     */
    public function __construct(
        VariantUpdateDeleteHelper $variantUpdateDeleteHelper,
        CheckForDeletedProductHelper $checkForDeletedProductHelper,
        ListCategoriesService $listCategoriesService
    ) {
        $this->variantUpdateDeleteHelper = $variantUpdateDeleteHelper;
        $this->checkForDeletedProductHelper = $checkForDeletedProductHelper;
        $this->listCategoriesService = $listCategoriesService;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        try {
            $currentScreen = WordPressRepository::getCurrentScreen();
            $productId = TypeHelper::int(ArrayHelper::get($_GET, 'post'), 0);

            if (
                $currentScreen instanceof Screen
                && CatalogIntegration::PRODUCT_POST_TYPE === $currentScreen->getObjectType()
                && 0 !== $productId
            ) {
                $this->prefetchCategories();
                $this->reconcileRemoteProducts($productId);
            }
        } catch(Exception|CommerceExceptionContract $e) {
            // we need to catch exceptions in hook callbacks to prevent fatal errors
            SentryException::getNewInstance('Failed to reconcile remote products during editing.', $e);
        }
    }

    /**
     * Prefetches categories and caches them.
     * We'll need to show all categories on this page anyway for the "Product Categories" selection, and loading them
     * now in one request helps prevent excessive API requests later.
     *
     * @return void
     */
    protected function prefetchCategories() : void
    {
        try {
            $this->listCategoriesService->list(
                ListCategoriesOperation::getNewInstance()
                    ->setPageSize(BatchRequestHelper::getMaxIdsPerRequest())
            );
        } catch(Exception|CommerceExceptionContract $e) {
            SentryException::getNewInstance('Failed to prefetch categories on the Edit Product page.', $e);
        }
    }
}

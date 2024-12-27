<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\AbstractDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers\ProductDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices\Flags\ProductInventoryUpdateFailedNoticeFlag;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices\ProductInventoryUpdateFailedNotice;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListSummariesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use WC_Product;
use WP_Post;

class ProductDataStoreInterceptor extends AbstractDataStoreInterceptor
{
    protected string $objectType = 'product';
    protected string $handler = ProductDataStoreHandler::class;

    protected ProductMapRepository $productMapRepository;
    protected InventoryProviderContract $inventoryProvider;
    protected SummariesServiceContract $summariesService;

    /**
     * @param ProductMapRepository $productMapRepository
     * @param InventoryProviderContract $inventoryProvider
     * @param SummariesServiceContract $summariesService
     */
    public function __construct(
        ProductMapRepository $productMapRepository,
        InventoryProviderContract $inventoryProvider,
        SummariesServiceContract $summariesService
    ) {
        $this->productMapRepository = $productMapRepository;
        $this->inventoryProvider = $inventoryProvider;
        $this->summariesService = $summariesService;
    }

    /**
     * {@inheritDoc}
     */
    public function addHooks() : void
    {
        parent::addHooks();

        Register::filter()
            ->setGroup('woocommerce_product_get_stock_quantity')
            ->setHandler([$this, 'maybeFilterStockQuantity'])
            ->setArgumentsCount(2)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_product_variation_get_stock_quantity')
            ->setHandler([$this, 'maybeFilterStockQuantity'])
            ->setArgumentsCount(2)
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'onAdminInit'])
            ->execute();
    }

    /**
     * Filters the product stock quantity with the quantity from the inventory service's summary value.
     *
     * @param mixed $quantity
     * @param mixed $product
     *
     * @return mixed
     */
    public function maybeFilterStockQuantity($quantity, $product)
    {
        if (! $product instanceof WC_Product || ! $product->managing_stock()) {
            return $quantity;
        }

        $localProductId = $product->get_stock_managed_by_id();

        if (! $remoteProductId = $this->productMapRepository->getRemoteId($localProductId)) {
            return $quantity;
        }

        if (! InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ)) {
            return $quantity;
        }

        try {
            $summaries = $this->summariesService->list(ListSummariesOperation::seed([
                'productIds' => [$remoteProductId],
            ]));

            foreach ($summaries->getSummaries() as $summary) {
                if ($summary->productId === $remoteProductId) {
                    $filteredQuantity = $this->isProductIndexOrEditPage() ? $summary->totalOnHand : $summary->totalAvailable;
                    $quantity = $product->backorders_allowed() ? $filteredQuantity : max($filteredQuantity, 0);
                    break;
                }
            }
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance("Could not read inventory summary for local product ID {$localProductId}", $exception);
        }

        return $quantity;
    }

    /**
     * Determines whether Products index screen or Product edit screen is loaded.
     *
     * @return bool
     */
    protected function isProductIndexOrEditPage() : bool
    {
        /** @var int|WP_Post|null $post * */
        $post = ArrayHelper::get($_GET, 'post');

        return (ArrayHelper::get($GLOBALS, 'pagenow') === 'edit.php'
                && ArrayHelper::get($_GET, 'post_type') === 'product') ||
            (ArrayHelper::get($GLOBALS, 'pagenow') === 'post.php'
                && ArrayHelper::get($_GET, 'action') === 'edit'
                && get_post_type($post) === 'product');
    }

    /**
     * Callback for the admin_init hook.
     *
     * @return void
     */
    public function onAdminInit() : void
    {
        $failFlag = ProductInventoryUpdateFailedNoticeFlag::getNewInstance();

        if ($failFlag->isOn()) {
            Notices::enqueueAdminNotice(ProductInventoryUpdateFailedNotice::getNewInstance($failFlag->getFailReason()));

            $failFlag->turnOff();
        }
    }
}

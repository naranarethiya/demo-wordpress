<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLevelRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\UpdateLevelFailedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\UpdateProductQuantityConflictEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Exceptions\SummaryNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\CreateOrUpdateLevelOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ReadLevelOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ReadSummaryOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LevelMapRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

trait CanCrudPlatformInventoryDataTrait
{
    protected LevelsServiceContract $levelsService;
    protected LevelsServiceWithCacheContract $levelsServiceWithCache;
    protected SummariesServiceContract $summariesService;
    protected InventoryProviderContract $inventoryProvider;
    protected LevelMapRepository $levelMapRepository;
    protected CommerceContextContract $commerceContext;

    /**
     * {@inheritDoc}
     *
     * This method is overridden to perform inventory updates after the product is created or updated.
     */
    protected function createOrUpdateProductInPlatform(WC_Product $product) : void
    {
        // temporarily disable reads so we don't double-increment inventory quantities
        if ($readsEnabled = InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ)) {
            InventoryIntegration::disableCapability(Commerce::CAPABILITY_READ);
        }

        try {
            // let the Catalog integration's datastore handle its operations (this may throw exceptions)
            parent::createOrUpdateProductInPlatform($product);
        } finally {
            // the below will be executed even if exceptions are thrown above

            // perform the updates if this product is managing stock and writes are enabled
            if ((InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE) && $this->shouldUpdateInventoryData($product))) {
                $this->createOrUpdatePlatformInventoryData($product);
            }
            // restore reads if they were previously enabled
            if ($readsEnabled) {
                InventoryIntegration::enableCapability(Commerce::CAPABILITY_READ);
            }
        }
    }

    /**
     * Determines if the inventory data for the given product should be updated.
     *
     * @param WC_Product $product
     * @return bool
     */
    protected function shouldUpdateInventoryData(WC_Product $product) : bool
    {
        return $this->productIsManagingOwnStock($product) && ($this->productIncludesInventoryChanges($product) || ! $this->productMappedInventoryLevel($product));
    }

    /**
     * Determines if a given product includes inventory changes.
     *
     * @param WC_Product $product
     * @return bool
     */
    protected function productIncludesInventoryChanges(WC_Product $product) : bool
    {
        $changes = $product->get_changes();

        return array_key_exists('backorders', $changes) || array_key_exists('stock_quantity', $changes) || array_key_exists('low_stock_amount', $changes);
    }

    /**
     * Determines if a given product includes inventory changes.
     *
     * @param WC_Product $product
     * @return string|null
     */
    protected function productMappedInventoryLevel(WC_Product $product) : ?string
    {
        return $this->levelMapRepository->getRemoteId($product->get_id());
    }

    /**
     * {@inheritDoc}
     *
     * This method is overridden to read the latest inventory counts for the given product.
     */
    protected function transformProduct(WC_Product $product) : WC_Product
    {
        // let the Catalog integration's datastore handle its transformations
        $product = parent::transformProduct($product);

        if ($this->productIsManagingOwnStock($product)) {
            $product = $this->applyLatestPlatformInventoryData($product);
        }

        return $product;
    }

    /**
     * Creates or updates inventory data in the platform for the given product.
     *
     * @param WC_Product $product
     */
    protected function createOrUpdatePlatformInventoryData(WC_Product $product) : void
    {
        $nativeProduct = null;

        try {
            $nativeProduct = ProductAdapter::getNewInstance($product)->convertFromSource();

            $level = $this->levelsServiceWithCache->createOrUpdateLevel(new CreateOrUpdateLevelOperation($nativeProduct))->getLevel();

            $this->applyPlatformInventoryLevel($product, $level);
        } catch (Exception|CommerceExceptionContract $exception) {
            Events::broadcast(UpdateLevelFailedEvent::getNewInstance($nativeProduct, $exception->getMessage()));
            SentryException::getNewInstance('An error occurred trying to create or update the remote inventory for a product: '.$exception->getMessage(), $exception);
        }
    }

    /**
     * Applies all the latest platform inventory data to the given product.
     *
     * This calls the various remote services to get & set the Woo product's properties.
     *
     * @param WC_Product $product
     *
     * @return WC_Product
     */
    protected function applyLatestPlatformInventoryData(WC_Product $product) : WC_Product
    {
        try {
            $nativeProduct = ProductAdapter::getNewInstance($product)->convertFromSource();

            // purposefully do not use the levels service with cache so this value is as fresh as possible
            $level = $this->levelsService->readLevelWithRepair(new ReadLevelOperation($nativeProduct))->getLevel();

            $level->quantity = $this->calculateLatestLevelQuantity($product, $level);

            $product = $this->applyPlatformInventoryLevel($product, $level);
        } catch (MissingLevelRemoteIdException|MissingProductRemoteIdException $exception) {
            // @TODO: Remove this catch block in MWC-12713 {acastro1 2023.06.15}
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('An error occurred trying to read the remote inventory for a product: '.$exception->getMessage(), $exception);
        }

        return $product;
    }

    /**
     * Applies the given level data to the given product.
     *
     * @param WC_Product $product
     * @param Level      $level
     *
     * @return WC_Product
     */
    protected function applyPlatformInventoryLevel(WC_Product $product, Level $level) : WC_Product
    {
        $product->set_stock_quantity($level->quantity);

        return $product;
    }

    /**
     * Calculates the latest stock quantity for the given product.
     *
     * If the remote quantity differs from the local, we treat the remote quantity as the source of truth and apply only
     * the difference between the local stored & local changed quantities to form the most up-to-date quantity.
     *
     * @param WC_Product $product
     * @param Level $level
     *
     * @return float
     * @throws Exception
     */
    protected function calculateLatestLevelQuantity(WC_Product $product, Level $level) : float
    {
        $remoteQuantity = $level->quantity;
        $existingLocalQuantity = TypeHelper::float(ArrayHelper::get($product->get_data(), 'stock_quantity', 0), 0);
        $newLocalQuantity = TypeHelper::float(ArrayHelper::get($product->get_changes(), 'stock_quantity', $existingLocalQuantity), 0);

        if ($remoteQuantity !== $existingLocalQuantity) {
            $difference = $newLocalQuantity - $existingLocalQuantity;

            $resolvedQuantity = $remoteQuantity + $difference;

            Events::broadcast(UpdateProductQuantityConflictEvent::getNewInstance($existingLocalQuantity, $remoteQuantity, $newLocalQuantity, $resolvedQuantity, ProductAdapter::getNewInstance($product)->convertFromSource()));

            $newLocalQuantity = $resolvedQuantity;
        }

        return $newLocalQuantity;
    }

    /**
     * Reads the product data.
     *
     * This first does the parent (WooCommerce) read, then reads the latest data from the inventory service and applies
     * it on top of that.
     *
     * @param mixed $product
     */
    protected function read_product_data(&$product) : void
    {
        // read from Woo
        parent::read_product_data($product);

        // sanity check for bad actors
        if (! $product instanceof WC_Product) {
            return;
        }

        // only if reads are enabled
        if (! InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ)) {
            return;
        }

        // only for products that are managing stock
        if (! $this->productIsManagingOwnStock($product)) {
            return;
        }

        // read from the platform
        $this->readPlatformInventoryData($product);
    }

    /**
     * Read the platform inventory data for the given product.
     *
     * @param WC_Product $product
     */
    protected function readPlatformInventoryData(WC_Product $product) : void
    {
        try {
            $this->readPlatformInventorySummary($product);
        } catch (MissingProductRemoteIdException $exception) {
            // @TODO: Remove this catch block in MWC-12713 {acastro1 2023.06.15}
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('An error occurred trying to read the remote inventory for a product: '.$exception->getMessage(), $exception);
        }
    }

    /**
     * Read the platform inventory level for the given product.
     *
     * @param WC_Product $product
     *
     * @return Level
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    protected function readPlatformInventoryLevel(WC_Product $product) : Level
    {
        $level = $this->levelsServiceWithCache->readLevel(new ReadLevelOperation(Product::getNewInstance()->setId($product->get_id())))->getLevel();

        $this->applyPlatformInventoryLevel($product, $level);

        return $level;
    }

    /**
     * Read the platform inventory summary for the given product.
     *
     * @param WC_Product $product
     *
     * @throws MissingProductRemoteIdException|CommerceExceptionContract|Exception
     */
    protected function readPlatformInventorySummary(WC_Product $product) : void
    {
        try {
            $foundSummary = $this->summariesService->readSummary(new ReadSummaryOperation($product->get_id()))->getSummary();

            $this->applyPlatformInventorySummary($product, $foundSummary);
        } catch (SummaryNotFoundException $exception) {
            // we do not need to report missing summaries to Sentry as the product may not be created in Commerce yet
        }
    }

    /**
     * Applies the given summary to the given product data.
     *
     * @param WC_Product $product
     * @param Summary $summary
     */
    protected function applyPlatformInventorySummary(WC_Product $product, Summary $summary) : void
    {
        $lowInventoryThreshold = $summary->lowInventoryThreshold;

        if (! is_null($lowInventoryThreshold)) {
            $lowInventoryThreshold = (int) $lowInventoryThreshold;
        }

        $product->set_low_stock_amount($lowInventoryThreshold ?? '');
        $product->set_stock_quantity($summary->totalOnHand ?? null);

        $backorders = $product->get_backorders();

        // preserve the Woo backorder setting if it equates to isBackorderable = true in the platform
        if ($summary->isBackorderable) {
            $backorders = 'no' === $backorders ? 'yes' : $backorders;
        } else {
            $backorders = 'no';
        }

        $product->set_backorders($backorders);
    }

    /**
     * Updates a product's stock quantity.
     *
     * @param mixed $productId
     * @param mixed $quantity
     * @param mixed $operation
     *
     * @return float
     */
    public function update_product_stock($productId, $quantity = null, $operation = 'set') : float
    {
        $wooProduct = ProductsRepository::get(TypeHelper::int($productId, 0));
        $quantity = TypeHelper::float($quantity, 0);

        // sanity check for bad values
        if (! $wooProduct instanceof WC_Product) {
            return $quantity;
        }

        try {
            $currentQuantity = $this->readPlatformInventoryLevel($wooProduct)->quantity;

            switch ($operation) {
                case 'increase':
                    $quantity = $currentQuantity + $quantity;
                    break;

                case 'decrease':
                    $quantity = $currentQuantity - $quantity;
                    break;
            }

            $nativeProduct = ProductAdapter::getNewInstance($wooProduct)->convertFromSource()
                ->setCurrentStock($quantity);

            $quantity = $this->levelsServiceWithCache->createOrUpdateLevel(new CreateOrUpdateLevelOperation($nativeProduct))->getLevel()->quantity;
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('An error occurred trying to update the remote inventory for a product: '.$exception->getMessage(), $exception);
        }

        // call the Woo method to fire hooks and update caches
        parent::update_product_stock($productId, $quantity);

        return $quantity;
    }

    /**
     * Determines if the given product is managing its own stock.
     *
     * This is false for variations that are inheriting stock from their parent.
     *
     * @param WC_Product $product
     *
     * @return bool
     */
    protected function productIsManagingOwnStock(WC_Product $product) : bool
    {
        return $product->managing_stock() && $product->get_id() === $product->get_stock_managed_by_id();
    }
}

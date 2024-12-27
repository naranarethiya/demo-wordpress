<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CheckoutDraftOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\PendingOrderStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGenerateIdContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Exceptions\MissingOrderRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\UpdateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\CreateOrderOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\UpdateOrderOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanCheckWooCommerceOrderTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use stdClass;
use WC_Abstract_Order_Data_Store_Interface;
use WC_Object_Data_Store_Interface;
use WC_Order;
use WC_Order_Data_Store_CPT;
use WC_Order_Data_Store_Interface;
use WC_Order_Item;
use WC_Order_Item_Product;

class OrderDataStore implements WC_Object_Data_Store_Interface, WC_Abstract_Order_Data_Store_Interface, WC_Order_Data_Store_Interface
{
    use CanCheckWooCommerceOrderTrait;

    /** @var WC_Object_Data_Store_Interface&WC_Abstract_Order_Data_Store_Interface&WC_Order_Data_Store_Interface */
    protected WC_Order_Data_Store_Interface $dataStore;

    protected OrdersServiceContract $ordersService;

    protected OrderReservationsServiceContract $orderReservationsService;

    protected WooOrderCartIdProvider $wooOrderCartIdProvider;

    protected CanGenerateIdContract $idProvider;

    protected BatchListProductsByLocalIdService $batchListProductsByLocalIdService;

    /**
     * @param WC_Object_Data_Store_Interface&WC_Abstract_Order_Data_Store_Interface&WC_Order_Data_Store_Interface $dataStore
     * @param OrdersServiceContract $ordersService
     * @param OrderReservationsServiceContract $orderReservationsService
     * @param WooOrderCartIdProvider $wooOrderCartIdProvider
     * @param CanGenerateIdContract $idProvider
     * @param BatchListProductsByLocalIdService $batchListProductsByLocalIdService
     */
    public function __construct(
        WC_Order_Data_Store_Interface $dataStore,
        OrdersServiceContract $ordersService,
        OrderReservationsServiceContract $orderReservationsService,
        WooOrderCartIdProvider $wooOrderCartIdProvider,
        CanGenerateIdContract $idProvider,
        BatchListProductsByLocalIdService $batchListProductsByLocalIdService
    ) {
        $this->dataStore = $dataStore;
        $this->ordersService = $ordersService;
        $this->orderReservationsService = $orderReservationsService;
        $this->wooOrderCartIdProvider = $wooOrderCartIdProvider;
        $this->idProvider = $idProvider;
        $this->batchListProductsByLocalIdService = $batchListProductsByLocalIdService;
    }

    /**
     * WooCommerce calls a query() method that exists in {@see OrdersTableDataStore} and {@see WC_Order_Data_Store_CPT} but is not part of a contract.
     *
     * @param string $name
     * @param mixed[] $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (! $this->isMethodExistInDataStore($name)) {
            SentryException::getNewInstance("Unexpected method call: the decorated data store does not have a {$name}() method defined.");

            return null;
        }

        return $this->dataStore->{$name}(...$arguments);
    }

    /**
     * Creates an order in the Commerce platform and WooCommerce's database.
     *
     * @param mixed $order
     */
    public function create(&$order) : void
    {
        if ($this->shouldCreateWooCommerceOrderInPlatform($order)) {
            $this->createWooCommerceOrderInPlatform($order);
        } else {
            $this->dataStore->create($order);
        }
    }

    /**
     * Determines whether we should use the given input to create a WooCommerce order in the Commerce platform.
     *
     * @param mixed $wooOrder
     * @return bool
     * @phpstan-assert-if-true WC_Order $wooOrder
     */
    protected function shouldCreateWooCommerceOrderInPlatform($wooOrder) : bool
    {
        return $this->canWriteWooCommerceOrderInPlatform($wooOrder) && ! $this->isWooCommerceOrderIncomplete($wooOrder);
    }

    /**
     * Determines whether we should use the given input to update a WooCommerce order in the Commerce platform.
     *
     * @param mixed $wooOrder
     * @return bool
     * @phpstan-assert-if-true WC_Order $wooOrder
     */
    protected function shouldUpdateWooCommerceOrderInPlatform($wooOrder) : bool
    {
        return $this->shouldCreateWooCommerceOrderInPlatform($wooOrder);
    }

    /**
     * Creates an order in the Commerce platform and WooCommerce's database.
     */
    protected function createWooCommerceOrderInPlatform(WC_Order &$wooOrder) : void
    {
        $wooOrder = $this->prepareOrderForPlatform($wooOrder);

        $order = $this->convertOrderForPlatform($wooOrder);

        if ($order) {
            $this->tryToCreateOrderInPlatform($order);
        }

        $this->dataStore->create($wooOrder);

        if ($order) {
            $order->setId($wooOrder->get_id());
        }
    }

    /**
     * Tries to create an existing order in the platform.
     *
     * @param UpdateOrderOperationContract $updateOrderOperation
     *
     * @return void
     */
    protected function tryToCreateExistingOrderInPlatform(UpdateOrderOperationContract $updateOrderOperation) : void
    {
        // We consider any order that changes from checkout-draft to pending status as new orders.
        // On a site that uses the checkout blocks experience, this change occurs when the merchant goes through the Checkout page and places the order.
        if ($updateOrderOperation->getOldWooCommerceOrderStatus() === (new CheckoutDraftOrderStatus())->getName() && $updateOrderOperation->getNewWooCommerceOrderStatus() === (new PendingOrderStatus())->getName()) {
            $this->tryToCreateOrderInPlatform($updateOrderOperation->getOrder());
        }
    }

    /**
     * Tries to create an order in the Commerce platform.
     */
    protected function tryToCreateOrderInPlatform(Order $order) : void
    {
        try {
            $this->createOrderInPlatform($order);
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance(
                'An error occurred trying to create a remote record for an order.',
                $exception
            );
        }
    }

    /**
     * Creates an order in the Commerce platform.
     *
     * @throws CommerceExceptionContract
     */
    protected function createOrderInPlatform(Order $order) : void
    {
        $this->orderReservationsService->createOrUpdateReservations($order);
        $this->ordersService->createOrder(CreateOrderOperation::fromOrder($order));
    }

    /**
     * Prepares a WooCommerce order to be used as the source for a Commerce order.
     */
    protected function prepareOrderForPlatform(WC_Order $wooOrder) : WC_Order
    {
        return $this->generateCartIdIfNotSet($wooOrder);
    }

    /**
     * Converts a WooCommerce order into an instance of the {@see Order} model.
     */
    protected function convertOrderForPlatform(WC_Order $wooOrder) : ?Order
    {
        try {
            return OrderAdapter::getNewInstance($wooOrder)->convertFromSource();
        } catch (AdapterException $exception) {
            SentryException::getNewInstance('An error occurred trying to convert the WooCommerce order into an Order instance.', $exception);
        }

        return null;
    }

    /**
     * Generates a cartId for the given WooCommerce order if one is not already set.
     *
     * @param WC_Order $wooOrder
     * @return WC_Order
     */
    protected function generateCartIdIfNotSet(WC_Order $wooOrder) : WC_Order
    {
        if (! $this->wooOrderCartIdProvider->getCartId($wooOrder)) {
            $this->wooOrderCartIdProvider->setCartId($wooOrder, $this->idProvider->generateId());
        }

        return $wooOrder;
    }

    /**
     * Obtains the old (previous) order status from the specified WC_Order.
     *
     * @param WC_Order $wooOrder
     *
     * @return string
     */
    protected function getOldWooCommerceOrderStatusForOrder(WC_Order $wooOrder) : string
    {
        return TypeHelper::string(ArrayHelper::get($wooOrder->get_data(), 'status', $wooOrder->get_status()), '');
    }

    /**
     * Updates an order in the Commerce platform and WooCommerce's database.
     *
     * @param mixed $order
     */
    public function update(&$order) : void
    {
        if ($this->shouldUpdateWooCommerceOrderInPlatform($order)) {
            $this->updateWooCommerceOrderInPlatform($order);
        }

        $this->dataStore->update($order);
    }

    /**
     * Attempts to update the giving WooCommerce order in the Commerce platform.
     *
     * @param WC_Order $wooOrder
     */
    protected function updateWooCommerceOrderInPlatform(WC_Order &$wooOrder) : void
    {
        $wooOrder = $this->prepareOrderForPlatform($wooOrder);

        if ($order = $this->convertOrderForPlatform($wooOrder)) {
            $this->tryToUpdateOrderInPlatform($this->makeUpdateOrderOperation($order, $wooOrder));
        }
    }

    /**
     * Updates the order on the MWCS platform.
     *
     * @param UpdateOrderOperationContract $updateOrderOperation The operation to use when updating the order.
     *
     * @return void
     */
    protected function tryToUpdateOrderInPlatform(UpdateOrderOperationContract $updateOrderOperation) : void
    {
        try {
            $this->ordersService->updateOrder($updateOrderOperation);
        } catch (MissingOrderRemoteIdException $exception) {
            $this->tryToCreateExistingOrderInPlatform($updateOrderOperation);
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance(
                'An error occurred trying to update a remote record for an order.',
                $exception
            );
        }
    }

    /**
     * Builds an instance of {@see UpdateOrderOperation} with given data.
     *
     * @param Order $order
     * @param WC_Order $wooOrder
     * @return UpdateOrderOperationContract
     */
    protected function makeUpdateOrderOperation(Order $order, WC_Order $wooOrder) : UpdateOrderOperationContract
    {
        return (new UpdateOrderOperation())
            ->setOrder($order)
            ->setNewWooCommerceOrderStatus($wooOrder->get_status())
            ->setOldWooCommerceOrderStatus($this->getOldWooCommerceOrderStatusForOrder($wooOrder));
    }

    /**
     * Method to read an order record.
     *
     * @param WC_Order $order Order object.
     * @throws Exception If passed order is invalid.
     */
    public function read(&$order) : void
    {
        $this->warmProductsCacheForOrders(ArrayHelper::wrap($order));

        /** @throws Exception {@see WC_Order_Data_Store_CPT::read()} */
        $this->dataStore->read($order);
    }

    /**
     * Reads multiple orders from custom tables in one pass.
     *
     * @param array<WC_Order> $orders Order objects.
     * @throws Exception If passed an invalid order.
     */
    public function read_multiple(&$orders) : void
    {
        if ($this->isMethodExistInDataStore('read_multiple')) {
            $this->warmProductsCacheForOrders($orders);
            /** @throws Exception */
            // Starting with phpstan v1.11.0, replace this phpstan-ignore-next-line with @phpstan-ignore method.notFound
            /* @phpstan-ignore-next-line doesn't recognize that we've tested for method existence. */
            $this->dataStore->read_multiple($orders);
        }
    }

    /**
     * Deletes a record from the database.
     *
     * This method returns false to fulfill the contract from {@see WC_Object_Data_Store_Interface::delete()}.
     * All known WooCommerce order data stores return void instead.
     *
     * @param WC_Order $order Data object.
     * @param mixed[] $args Array of args to pass to the delete method.
     * @return false
     */
    public function delete(&$order, $args = []) : bool
    {
        $this->dataStore->delete($order, $args);

        return false;
    }

    /**
     * Returns an array of meta for an object.
     *
     * @param WC_Order $order Data object.
     * @return stdClass[]
     */
    public function read_meta(&$order) : array
    {
        return $this->dataStore->read_meta($order);
    }

    /**
     * Deletes meta based on meta ID.
     *
     * This method returns an empty array to fulfill the contract from {@see WC_Object_Data_Store_Interface::delete_meta()}.
     * All known WooCommerce order data stores return bool instead.
     *
     * @param WC_Order $order Data object.
     * @param object $meta Meta object (containing at least ->id).
     * @return array{}
     */
    public function delete_meta(&$order, $meta) : array
    {
        $this->dataStore->delete_meta($order, $meta);

        return [];
    }

    /**
     * Add new piece of meta.
     *
     * @param WC_Order $order Data object.
     * @param object $meta Meta object (containing ->key and ->value).
     * @return int meta ID
     */
    public function add_meta(&$order, $meta) : int
    {
        return TypeHelper::int($this->dataStore->add_meta($order, $meta), 0);
    }

    /**
     * Update meta.
     *
     * @param WC_Order $order Data object.
     * @param object $meta Meta object (containing ->id, ->key and ->value).
     * @return void
     */
    public function update_meta(&$order, $meta)
    {
        $this->dataStore->update_meta($order, $meta);
    }

    /**
     * Read order items of a specific type from the database for this order.
     *
     * @param WC_Order $order Order object.
     * @param string $type Order item type.
     * @return WC_Order_Item[]
     */
    public function read_items($order, $type) : array
    {
        return $this->dataStore->read_items($order, $type);
    }

    /**
     * Remove all line items (products, coupons, shipping, taxes) from the order.
     *
     * @param WC_Order $order Order object.
     * @param string $type Order item type. Default null.
     * @return void
     */
    public function delete_items($order, $type = null) : void
    {
        $this->dataStore->delete_items($order, $type);
    }

    /**
     * Get token ids for an order.
     *
     * @param WC_Order $order Order object.
     * @return string[]
     */
    public function get_payment_token_ids($order) : array
    {
        return $this->dataStore->get_payment_token_ids($order);
    }

    /**
     * Update token ids for an order.
     *
     * @param WC_Order $order Order object.
     * @param string[] $token_ids Token IDs.
     */
    public function update_payment_token_ids($order, $token_ids) : void
    {
        $this->dataStore->update_payment_token_ids($order, $token_ids);
    }

    /**
     * Get amount already refunded.
     *
     * @param WC_Order $order Order object.
     * @return float
     */
    public function get_total_refunded($order) : float
    {
        return $this->dataStore->get_total_refunded($order);
    }

    /**
     * Get the total tax refunded.
     *
     * @param WC_Order $order Order object.
     * @return float
     */
    public function get_total_tax_refunded($order) : float
    {
        return $this->dataStore->get_total_tax_refunded($order);
    }

    /**
     * Get the total shipping refunded.
     *
     * @param WC_Order $order Order object.
     * @return float
     */
    public function get_total_shipping_refunded($order) : float
    {
        return $this->dataStore->get_total_shipping_refunded($order);
    }

    /**
     * Finds an Order ID based on an order key.
     *
     * @param string $order_key An order key has generated by.
     * @return int The ID of an order, or 0 if the order could not be found.
     */
    public function get_order_id_by_order_key($order_key) : int
    {
        return $this->dataStore->get_order_id_by_order_key($order_key);
    }

    /**
     * Return count of orders with a specific status.
     *
     * @param string $status Order status.
     * @return int
     */
    public function get_order_count($status) : int
    {
        return $this->dataStore->get_order_count($status);
    }

    /**
     * Get all orders matching the passed in args.
     *
     * @see wc_get_orders()
     *
     * @param mixed[] $args Arguments.
     * @return WC_Order[] of orders
     */
    public function get_orders($args = []) : array
    {
        return TypeHelper::arrayOf($this->dataStore->get_orders($args), WC_Order::class);
    }

    /**
     * Get unpaid orders after a certain date,.
     *
     * @param int $date timestamp.
     * @return WC_Order[]
     */
    public function get_unpaid_orders($date) : array
    {
        return $this->dataStore->get_unpaid_orders($date);
    }

    /**
     * Search order data for a term and return ids.
     *
     * @param string $term Term name.
     * @return int[] of ids
     */
    public function search_orders($term) : array
    {
        return $this->dataStore->search_orders($term);
    }

    /**
     * Gets information about whether permissions were generated yet.
     *
     * @param WC_Order $order Order object.
     * @return bool
     */
    public function get_download_permissions_granted($order) : bool
    {
        return $this->dataStore->get_download_permissions_granted($order);
    }

    /**
     * Stores information about whether permissions were generated yet.
     *
     * @param WC_Order $order Order object.
     * @param bool $set If should set.
     */
    public function set_download_permissions_granted($order, $set) : void
    {
        $this->dataStore->set_download_permissions_granted($order, $set);
    }

    /**
     * Gets information about whether sales were recorded.
     *
     * @param WC_Order $order Order object.
     * @return bool
     */
    public function get_recorded_sales($order) : bool
    {
        return $this->dataStore->get_recorded_sales($order);
    }

    /**
     * Stores information about whether sales were recorded.
     *
     * @param WC_Order $order Order object.
     * @param bool $set If should set.
     */
    public function set_recorded_sales($order, $set) : void
    {
        $this->dataStore->set_recorded_sales($order, $set);
    }

    /**
     * Gets information about whether coupon counts were updated.
     *
     * @param WC_Order $order Order object.
     * @return bool
     */
    public function get_recorded_coupon_usage_counts($order) : bool
    {
        return $this->dataStore->get_recorded_coupon_usage_counts($order);
    }

    /**
     * Stores information about whether coupon counts were updated.
     *
     * @param WC_Order $order Order object.
     * @param bool $set If it should set.
     */
    public function set_recorded_coupon_usage_counts($order, $set) : void
    {
        $this->dataStore->set_recorded_coupon_usage_counts($order, $set);
    }

    /**
     * Get the order type based on Order ID.
     *
     * @param int $order_id Order ID.
     * @return string
     */
    public function get_order_type($order_id) : string
    {
        return $this->dataStore->get_order_type($order_id);
    }

    /**
     * Get order types for given Order IDs.
     *
     * @param int[] $orderIds Order IDs.
     *
     * @return array<int, string>
     */
    public function get_orders_type(array $orderIds) : array
    {
        if ($this->isMethodCallableInDataStore('get_orders_type')) {
            /* @phpstan-ignore-next-line doesn't recognize that we've tested if method is callable. */
            return $this->dataStore->get_orders_type($orderIds);
        }

        $ordersType = [];
        foreach ($orderIds as $orderId) {
            $ordersType[$orderId] = $this->get_order_type($orderId);
        }

        return $ordersType;
    }

    /**
     * Is the given method callable in dataStore?
     *
     * @codeCoverageIgnore
     *
     * @param string $method
     * @return bool
     */
    protected function isMethodCallableInDataStore(string $method) : bool
    {
        return is_callable([$this->dataStore, $method]);
    }

    /**
     * Does the given method exist in dataStore?
     *
     * @codeCoverageIgnore
     *
     * @param string $method
     *
     * @return bool
     */
    protected function isMethodExistInDataStore(string $method) : bool
    {
        return method_exists($this->dataStore, $method);
    }

    /**
     * Populates the cache for all products belonging to the given orders.
     *
     * @param WC_Order[] $orders
     */
    protected function warmProductsCacheForOrders(array $orders) : void
    {
        $this->batchListProductsByLocalIdService->batchListByLocalIds($this->getLocalProductIdsFromOrderItems($orders));
    }

    /**
     * Gets local product IDs for all the products belonging to the given orders.
     *
     * @param WC_Order[] $orders
     *
     * @return int[]
     */
    protected function getLocalProductIdsFromOrderItems(array $orders) : array
    {
        return array_unique(
            array_filter(
                array_map(
                    static function ($item) : ?int {
                        if ($item instanceof WC_Order_Item_Product) {
                            return $item->get_product_id();
                        }

                        return null;
                    },
                    ArrayHelper::flatten(array_map(static fn (WC_Order $order) => $order->get_items('line_item'), $orders))
                )
            )
        );
    }
}

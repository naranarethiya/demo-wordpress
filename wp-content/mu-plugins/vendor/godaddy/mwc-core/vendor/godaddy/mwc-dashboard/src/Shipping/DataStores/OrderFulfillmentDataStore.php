<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\OrderAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters\LineItemFulfillmentStatusAdapter;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters\OrderFulfillmentStatusAdapter;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\WooCommerce\Adapters\ShipmentAdapter;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\Traits\CanManipulateOrderMetaDataTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\PartiallyFulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\UnfulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Order fulfillment data store class.
 */
class OrderFulfillmentDataStore
{
    use CanManipulateOrderMetaDataTrait;

    /** @var string */
    protected const FULFILLMENT_STATUS_META_KEY = '_mwc_fulfillment_status';

    /** @var string */
    protected const ORDER_FULFILLMENT_META_KEY = '_mwc_order_fulfillment';

    /** @var DataSourceAdapterContract[] an array of class names for provider specific implementations of DataSourceAdapterContract */
    protected $adapters = [];

    /**
     * Gets the value for the _mwc_order_fulfillment meta entry for the given order ID.
     *
     * @param int|null $orderId
     * @return OrderFulfillment|null
     */
    public function read(int $orderId = null)
    {
        if (null === $orderId) {
            return null;
        }

        if (! $wcOrder = OrdersRepository::get((int) $orderId)) {
            return null;
        }

        $fulfillment = new OrderFulfillment();

        $order = $this->getOrderAdapter($wcOrder)->convertFromSource();

        $fulfillment->setOrder($order);

        $shipments = ArrayHelper::get($wcOrder->get_meta('_mwc_order_fulfillment'), 'shipments', []);

        foreach ($shipments as $shipmentData) {
            if ($shipment = $this->getShipment($shipmentData)) {
                $fulfillment->addShipment($shipment);
            }
        }

        $this->setOrderFulfillmentStatus($fulfillment, $wcOrder);
        $this->setFulfillmentStatusForLineItems($fulfillment->getLineItemsThatNeedShipping());

        return $fulfillment;
    }

    /**
     * Creates an instance of ShipmentContract using the given source data.
     *
     * @since x.y.z
     *
     * @param array $data
     * @return ShipmentContract
     */
    protected function getShipment(array $data) : ShipmentContract
    {
        return $this->getShipmentAdapter(ArrayHelper::get($data, 'providerName', ''), $data)->convertFromSource();
    }

    /**
     * Creates an instance of the class defined in the $adapters property for the specified provider.
     *
     * @since x.y.z
     *
     * @param string $provider
     * @param array $data
     * @return ShipmentAdapter
     */
    protected function getShipmentAdapter(string $provider, array $data = [])
    {
        if (array_key_exists($provider, $this->adapters)) {
            return new $this->adapters[$provider]($data);
        }

        return new ShipmentAdapter($data);
    }

    /**
     * Sets the fulfillment status for the Order object using the value from the _mwc_fulfillment_status order meta.
     *
     * @param OrderFulfillment $orderFulfillment Order Fulfillment object
     * @param WC_Order $wcOrder WooCommerce Order object
     */
    protected function setOrderFulfillmentStatus(OrderFulfillment $orderFulfillment, WC_Order $wcOrder) : void
    {
        OrderFulfillmentStatusAdapter::getNewInstance($wcOrder)->convertFromSource($orderFulfillment->getOrder());
    }

    /**
     * Updates the _mwc_order_fulfillment order meta entry.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     * @throws \Exception
     */
    public function save(OrderFulfillment $fulfillment = null)
    {
        if (empty($fulfillment)) {
            return;
        }

        $order = $fulfillment->getOrder();

        if (empty($order)) {
            return;
        }

        $wcOrderInstance = OrdersRepository::get($order->getId());

        if (empty($wcOrderInstance)) {
            return;
        }

        $this->updateFulfillmentOrderStatuses($fulfillment, $wcOrderInstance);
        $this->addOrderMeta($wcOrderInstance, static::ORDER_FULFILLMENT_META_KEY, $this->getOrderShipmentsArray($fulfillment));

        // TODO: remove this call to WC_Order::save() in https://godaddy-corp.atlassian.net/browse/MWC-13394
        $wcOrderInstance->save();
    }

    /**
     * Updates the fulfillment order statuses meta _mwc_fulfillment_status for the order and line items.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     * @param WC_Order $wcOrderInstance
     * @return OrderFulfillmentDataStore
     * @throws \Exception
     */
    protected function updateFulfillmentOrderStatuses(OrderFulfillment $fulfillment, WC_Order $wcOrderInstance) : OrderFulfillmentDataStore
    {
        $this->updateFulfillmentStatusForLineItems($fulfillment->getLineItemsThatNeedShipping());
        $this->addOrderMeta($wcOrderInstance, '_mwc_fulfillment_status', $fulfillment->getOrder()->getFulfillmentStatus()->getName());

        return $this;
    }

    /**
     * Creates and populates the order array with shipments.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     * @return array
     */
    protected function getOrderShipmentsArray(OrderFulfillment $fulfillment) : array
    {
        $order = [
            'shipments' => [],
        ];

        foreach ($fulfillment->getShipments() as $shipment) {
            $order['shipments'][] = $this->getShipmentAdapter($shipment->getProviderName(), [])->convertToSource($shipment);
        }

        return $order;
    }

    /**
     *  Updates the fulfilled status for line items.
     *
     * @param array $lineItems
     * @throws \Exception
     */
    protected function updateFulfillmentStatusForLineItems(array $lineItems)
    {
        foreach ($lineItems as $lineItem) {
            $this->addOrderItemMeta($lineItem, '_mwc_fulfillment_status', $lineItem->getFulfillmentStatus()->getName());
        }
    }

    /**
     *  Wraps the call to add_meta_data.
     *
     * @param WC_Order $order
     * @param string $key
     * @param $value
     * @return mixed
     */
    protected function addOrderMeta(WC_Order $order, string $key, $value)
    {
        $order->update_meta_data($key, $value);
        $order->save_meta_data();
    }

    /**
     *  Wraps the call to wc_update_order_item_meta.
     *
     * @param LineItem $lineItem
     * @return bool
     * @throws \Exception
     */
    protected function addOrderItemMeta(LineItem $lineItem, string $key, $value) : bool
    {
        return wc_update_order_item_meta($lineItem->getId(), $key, $value);
    }

    /**
     * Deletes the fulfillment metadata for the associated order.
     *
     * @param OrderFulfillment|null $fulfillment
     * @return bool
     */
    public function delete(OrderFulfillment $fulfillment = null) : bool
    {
        if (empty($fulfillment)) {
            return false;
        }

        foreach ($fulfillment->getLineItemsThatNeedShipping() as $item) {
            $this->deleteOrderItemMetaData($item->getId(), static::FULFILLMENT_STATUS_META_KEY);
        }

        $this->deleteOrderMetaData((int) $fulfillment->getOrder()->getId(), [static::FULFILLMENT_STATUS_META_KEY, static::ORDER_FULFILLMENT_META_KEY]);

        return true;
    }

    /**
     * Given an array of LineItem objects, set the fulfillment status as stored in the _mwc_fulfillment_status
     * meta entry.
     *
     * @param array<LineItem> $lineItems
     */
    protected function setFulfillmentStatusForLineItems(array $lineItems) : void
    {
        foreach ($lineItems as $lineItem) {
            $wcOrderItemProduct = new WC_Order_Item_Product($lineItem->getId());
            LineItemFulfillmentStatusAdapter::getNewInstance($wcOrderItemProduct)->convertFromSource($lineItem);
        }
    }

    /**
     * Translate the value of the _mwc_fulfillment_status meta entry into one of our FulfillmentStatus classes.
     *
     * @since x.y.z
     *
     * @param string $name
     * @return FulfillmentStatusContract
     */
    protected function getFulfillmentStatusByName(string $name) : FulfillmentStatusContract
    {
        $statusList = [new FulfilledFulfillmentStatus(), new PartiallyFulfilledFulfillmentStatus(), new UnfulfilledFulfillmentStatus()];

        foreach ($statusList as $status) {
            if ($name === $status->getName()) {
                return $status;
            }
        }

        return new UnfulfilledFulfillmentStatus();
    }

    /**
     * Gets an instance of the Order Adapter.
     *
     * @param WC_Order $order
     * @return DataSourceAdapterContract
     */
    protected function getOrderAdapter(WC_Order $order) : DataSourceAdapterContract
    {
        return new OrderAdapter($order);
    }
}

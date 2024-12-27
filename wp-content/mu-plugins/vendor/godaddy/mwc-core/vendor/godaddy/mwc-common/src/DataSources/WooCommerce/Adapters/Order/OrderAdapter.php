<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use DateTime;
use DateTimezone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Builders\Orders\OrderNoteBuilder;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\AbstractOrderItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\FeeItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Common\Models\Orders\ShippingItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CancelledOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CheckoutDraftOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CompletedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\FailedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\HeldOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\PendingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\ProcessingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\RefundedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\TaxItem;
use stdClass;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Order_Item_Tax;

/**
 * Order adapter.
 *
 * Converts between a native order object and a WooCommerce order object.
 */
class OrderAdapter implements DataSourceAdapterContract
{
    /** @var WC_Order WooCommerce order object */
    protected WC_Order $source;

    /** @var class-string<Order> the order class name */
    protected $orderClass = Order::class;

    /** @var array<string, class-string<OrderStatusContract>> order status relationships */
    protected array $statuses = [
        'cancelled'      => CancelledOrderStatus::class,
        'completed'      => CompletedOrderStatus::class,
        'failed'         => FailedOrderStatus::class,
        'on-hold'        => HeldOrderStatus::class,
        'pending'        => PendingOrderStatus::class,
        'processing'     => ProcessingOrderStatus::class,
        'refunded'       => RefundedOrderStatus::class,
        'checkout-draft' => CheckoutDraftOrderStatus::class,
    ];

    /** @var class-string<LineItemAdapter> class that is used as line item adapter. Can be overwritten by inheritors. */
    protected $lineItemAdapterClass = LineItemAdapter::class;

    /**
     * Order adapter constructor.
     *
     * @param WC_Order $order WooCommerce order object.
     */
    public function __construct(WC_Order $order)
    {
        $this->source = $order;
    }

    /**
     * Converts a WooCommerce order object into a native order object.
     *
     * @return Order
     * @throws AdapterException
     */
    public function convertFromSource() : Order
    {
        /** @var Order $order */
        $order = (new $this->orderClass())
            ->setId($this->source->get_id())
            ->setNumber($this->source->get_order_number())
            ->setNotes($this->getOrderNotesFromSource());

        if ($status = $this->convertStatusFromSource()) {
            $order->setStatus($status);
        }

        // dates
        try {
            if ($createdAt = $this->source->get_date_created()) {
                $order->setCreatedAt(new DateTime($createdAt->format('c')));
            }
            if ($updatedAt = $this->source->get_date_modified()) {
                $order->setUpdatedAt(new DateTime($updatedAt->format('c')));
            }
            if ($completedAt = $this->source->get_date_completed()) {
                $order->setCompletedAt(new DateTime($completedAt->format('c')));
            }

            // we pass 'edit' as the context parameter for get_date_paid() to prevent the `woocommerce_payment_complete_order_status`
            // filter from being triggered for orders that haven't been saved
            // https://godaddy-corp.atlassian.net/browse/MWC-13191
            if ($paidAt = $this->source->get_date_paid('edit')) {
                $order->setPaidAt(new DateTime($paidAt->format('c')));
            }
        } catch (Exception $exception) {
            throw new AdapterException($exception->getMessage(), $exception);
        }

        // customer data
        $order->setCustomerId($this->source->get_customer_id());
        if ($ip = $this->source->get_customer_ip_address()) {
            $order->setCustomerIpAddress($ip);
        }

        // addresses
        $order
            ->setBillingAddress((new AddressAdapter($this->source->get_address('billing')))->convertFromSource())
            ->setShippingAddress((new AddressAdapter($this->source->get_address('shipping')))->convertFromSource());

        // order items
        $order = $this->convertOrderItemsFromSource($order);

        // order amounts
        $order->setLineAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_subtotal()));
        $order->setShippingAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_shipping_total()));
        $order->setFeeAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_fees()));
        $order->setTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_tax()));
        $order->setTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total()));

        // payment
        $order->setPaymentMethod($this->source->get_payment_method() ?? '');

        return $order;
    }

    /**
     * Converts the source's status to a normalized status object.
     *
     * @return OrderStatusContract|null
     */
    protected function convertStatusFromSource() : ?OrderStatusContract
    {
        if (! $statusClass = TypeHelper::string(ArrayHelper::get($this->statuses, $this->source->get_status()), '')) {
            return null;
        }

        if (! is_a($statusClass, OrderStatusContract::class, true)) {
            return null;
        }

        return new $statusClass();
    }

    /**
     * Converts WooCommerce order items into native order items counterparts.
     *
     * @param Order $order
     * @return Order
     */
    private function convertOrderItemsFromSource(Order $order) : Order
    {
        $feeItems = $lineItems = $shippingItems = $taxItems = [];

        foreach (['fee', 'line_item', 'shipping', 'tax'] as $itemsKey) {
            foreach ($this->source->get_items($itemsKey) as $item) {
                if ($item instanceof WC_Order_Item_Fee) {
                    $feeItems[] = FeeItemAdapter::for($item, $this->source)->convertFromSource();
                }

                if ($item instanceof WC_Order_Item_Product) {
                    $lineItems[] = $this->lineItemAdapterClass::for($item, $this->source)->convertFromSource();
                }

                if ($item instanceof WC_Order_Item_Shipping) {
                    $shippingItems[] = ShippingItemAdapter::for($item, $this->source)->convertFromSource();
                }

                if ($item instanceof WC_Order_Item_Tax) {
                    $taxItems[] = TaxItemAdapter::for($item, $this->source)->convertFromSource();
                }
            }
        }

        return $order
            ->setFeeItems($feeItems)
            ->setLineItems($lineItems)
            ->setShippingItems($shippingItems)
            ->setTaxItems($taxItems);
    }

    /**
     * Converts an order amount from source.
     *
     * @param float $amount
     * @return CurrencyAmount
     */
    private function convertCurrencyAmountFromSource(float $amount) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter($amount, $this->source->get_currency()))->convertFromSource();
    }

    /**
     * Converts a native order object into a WooCommerce order object.
     *
     * @param Order|null $order native order object to convert
     * @return WC_Order WooCommerce order object
     * @throws AdapterException
     */
    public function convertToSource($order = null) : WC_Order
    {
        if (! $order instanceof Order) {
            return $this->source;
        }

        try {
            $this->source->set_id($order->getId());

            if ($status = $order->getStatus()) {
                $this->source->set_status($this->convertStatusToSource($status));
            }

            // dates
            if ($dateCreated = $order->getCreatedAt()) {
                $this->source->set_date_created($dateCreated->setTimezone(new DateTimeZone('UTC'))->getTimestamp());
            }
            if ($dateUpdated = $order->getUpdatedAt()) {
                $this->source->set_date_modified($dateUpdated->setTimezone(new DateTimeZone('UTC'))->getTimestamp());
            }
            if ($dateCompleted = $order->getCompletedAt()) {
                $this->source->set_date_completed($dateCompleted->setTimezone(new DateTimezone('UTC'))->getTimestamp());
            }
            if ($datePaid = $order->getPaidAt()) {
                $this->source->set_date_paid($datePaid->setTimezone(new DateTimezone('UTC'))->getTimestamp());
            }

            // customer data
            $this->source->set_customer_id($order->getCustomerId());
            $this->source->set_customer_ip_address($order->getCustomerIpAddress());

            // addresses
            $this->source->set_address((new AddressAdapter([]))->convertToSource($order->getBillingAddress()), 'billing');
            $this->source->set_address((new AddressAdapter([]))->convertToSource($order->getShippingAddress()), 'shipping');

            // order items
            $this->convertOrderItemsToSource($order);

            // totals
            if ($shippingAmount = $order->getShippingAmount()) {
                $this->source->set_shipping_total((string) CurrencyAmountAdapter::getNewInstance(0.0, $shippingAmount->getCurrencyCode())->convertToSource($shippingAmount));
            }
            if ($taxAmount = $order->getTaxAmount()) {
                $this->source->set_cart_tax((string) CurrencyAmountAdapter::getNewInstance(0.0, $taxAmount->getCurrencyCode())->convertToSource($taxAmount));
            }
            if ($totalAmount = $order->getTotalAmount()) {
                $this->source->set_total((string) CurrencyAmountAdapter::getNewInstance(0.0, $totalAmount->getCurrencyCode())->convertToSource($totalAmount));
            }
        } catch (Exception $exception) {
            throw new AdapterException($exception->getMessage(), $exception);
        }

        return $this->source;
    }

    /**
     * Converts native order items to WooCommerce order items.
     *
     * @param Order $order
     * @throws AdapterException
     */
    protected function convertOrderItemsToSource(Order $order) : void
    {
        try {
            foreach ($order->getFeeItems() as $feeItem) {
                $this->source->add_item($this->convertFeeItemToSource($order, $feeItem));
            }

            foreach ($order->getLineItems() as $lineItem) {
                $this->source->add_item($this->convertLineItemToSource($order, $lineItem));
            }

            foreach ($order->getShippingItems() as $shippingItem) {
                $this->source->add_item($this->convertShippingItemToSource($order, $shippingItem));
            }

            foreach ($order->getTaxItems() as $taxItem) {
                $this->source->add_item($this->convertTaxItemToSource($order, $taxItem));
            }
        } catch (Exception $exception) {
            throw new AdapterException($exception->getMessage(), $exception);
        }
    }

    /**
     * Converts a {@see FeeItem} instance into a {@see WC_Order_Item_Fee} instance.
     *
     * @param Order $order
     * @param FeeItem $feeItem
     *
     * @return WC_Order_Item_Fee
     */
    protected function convertFeeItemToSource(Order $order, FeeItem $feeItem) : WC_Order_Item_Fee
    {
        $wooFeeItem = $this->getWooCommerceOrderItemInstanceFromSource($order, $feeItem, 'fee', WC_Order_Item_Fee::class);

        return FeeItemAdapter::for($wooFeeItem, $this->source)->convertToSource($feeItem);
    }

    /**
     * Converts a {@see LineItem} instance into a {@see WC_Order_Item_Product} instance.
     *
     * @param Order $order
     * @param LineItem $lineItem
     *
     * @return WC_Order_Item_Product
     */
    protected function convertLineItemToSource(Order $order, LineItem $lineItem) : WC_Order_Item_Product
    {
        $wooLineItem = $this->getWooCommerceOrderItemInstanceFromSource($order, $lineItem, 'line_item', WC_Order_Item_Product::class);

        return $this->lineItemAdapterClass::for($wooLineItem, $this->source)->convertToSource($lineItem);
    }

    /**
     * Converts a {@see ShippingItem} instance into a {@see WC_Order_Item_Shipping} instance.
     *
     * @param Order $order
     * @param ShippingItem $shippingItem
     *
     * @return WC_Order_Item_Shipping
     * @throws AdapterException
     */
    protected function convertShippingItemToSource(Order $order, ShippingItem $shippingItem) : WC_Order_Item_Shipping
    {
        $wooShippingItem = $this->getWooCommerceOrderItemInstanceFromSource($order, $shippingItem, 'shipping', WC_Order_Item_Shipping::class);

        return ShippingItemAdapter::for($wooShippingItem, $this->source)->convertToSource($shippingItem);
    }

    /**
     * Converts a {@see TaxItem} instance into a {@see WC_Order_Item_Tax} instance.
     *
     * @param Order $order
     * @param TaxItem $taxItem
     *
     * @return WC_Order_Item_Tax
     */
    protected function convertTaxItemToSource(Order $order, TaxItem $taxItem) : WC_Order_Item_Tax
    {
        $wooTaxItem = $this->getWooCommerceOrderItemInstanceFromSource($order, $taxItem, 'tax', WC_Order_Item_Tax::class);

        return TaxItemAdapter::for($wooTaxItem, $this->source)->convertToSource($taxItem);
    }

    /**
     * Converts the given status object to the source status.
     *
     * @param OrderStatusContract $status
     * @return string
     */
    protected function convertStatusToSource(OrderStatusContract $status) : string
    {
        $statuses = array_flip($this->statuses);

        return $statuses[get_class($status)] ?? $status->getName();
    }

    /**
     * Gets a WooCommerce order item instance of a given type.
     *
     * @internal
     *
     * @template TOrderItemClass of WC_Order_Item
     * @param class-string<TOrderItemClass> $itemClass class name of the item to instantiate
     * @param int $orderId associated Order ID
     * @return TOrderItemClass
     */
    public function getWooCommerceOrderItemInstance(string $itemClass, int $orderId)
    {
        $item = new $itemClass();

        if (method_exists($item, 'set_order_id')) {
            $item->set_order_id($orderId);
        }

        return $item;
    }

    /**
     * Gets a WooCommerce order item from the list of items in the source order.
     * @note When an existing WooCommerce order is used as source, it's important to use existing order item instances
     *    as the source for the line item adapters as well, to make sure that all the original values that the adapters
     *    don't change are preserved.
     *
     * @template TOrderItemClass of WC_Order_Item
     * @param Order $order
     * @param AbstractOrderItem $orderItem
     * @param non-empty-string $itemType
     * @param class-string<TOrderItemClass> $itemClass
     * @return TOrderItemClass
     */
    protected function getWooCommerceOrderItemInstanceFromSource(
        Order $order,
        AbstractOrderItem $orderItem,
        string $itemType,
        string $itemClass
    ) {
        $item = ArrayHelper::get($this->source->get_items($itemType), (string) $orderItem->getId());

        if ($item instanceof $itemClass) {
            return $item;
        }

        return $this->getWooCommerceOrderItemInstance($itemClass, (int) $order->getId());
    }

    /**
     * Gets the notes stored in this order.
     *
     * @return Note[]
     */
    public function getOrderNotesFromSource() : array
    {
        if (! $this->source->get_id()) {
            return [];
        }

        /** @var Note[] $result */
        $result = array_map([$this, 'buildNoteFromSource'], wc_get_order_notes(['order_id' => $this->source->get_id()]));

        return $result;
    }

    /**
     * Converts the given note into a Note object.
     *
     * @param stdClass $note
     *
     * @return Note
     */
    protected function buildNoteFromSource(stdClass $note) : Note
    {
        return OrderNoteBuilder::getNewInstance()->setData($note)->build();
    }
}

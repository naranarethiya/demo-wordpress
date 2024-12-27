<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\OrderAdapter as CommonOrderAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CancelledOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CheckoutDraftOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\FailedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\RefundedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order as CoreOrder;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters\LineItemFulfillmentStatusAdapter;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters\OrderFulfillmentStatusAdapter;
use WC_Order;
use WC_Order_Item_Product;

/**
 * The Core's Order adapter.
 *
 * Converts between a native core order object and a WooCommerce order object.
 *
 * @method static static getNewInstance(WC_Order $order)
 */
class OrderAdapter extends CommonOrderAdapter
{
    use CanGetNewInstanceTrait;

    /** @var string the Marketplaces channel name order meta key */
    const MARKETPLACES_CHANNEL_NAME_META_KEY = '_marketplaces_channel_name';

    /** @var string the Marketplaces channel type order meta key */
    const MARKETPLACES_CHANNEL_TYPE_META_KEY = '_marketplaces_channel_type';

    /** @var string the Marketplaces channel uuid order meta key */
    const MARKETPLACES_CHANNEL_UUID_META_KEY = '_marketplaces_channel_uuid';

    /** @var string the Marketplaces order status meta key */
    const MARKETPLACES_ORDER_STATUS = '_marketplaces_order_status';

    /** @var string the Marketplaces internal order number order meta key */
    const MARKETPLACES_INTERNAL_ORDER_NUMBER_META_KEY = '_marketplaces_internal_order_number';

    /** @var string the Marketplaces display order number order meta key */
    const MARKETPLACES_DISPLAY_ORDER_NUMBER_META_KEY = '_marketplaces_display_order_number';

    /** @var string the Marketplaces channel order reference meta key */
    const MARKETPLACES_CHANNEL_ORDER_REFERENCE_META_KEY = '_marketplaces_channel_order_reference';

    /** @var string the Marketplaces internal order item id order meta key */
    const MARKETPLACES_INTERNAL_ORDER_ITEM_ID_META_KEY = '_marketplaces_internal_order_item_id';

    /** @var string meta key for the ID referencing the originating channel in the Channels API */
    const ORIGINATING_CHANNEL_ID_META_KEY = '_originating_channel_id';

    /** {@inheritDoc} */
    protected $orderClass = CoreOrder::class;

    /** @var class-string<LineItemAdapter> */
    protected $lineItemAdapterClass = LineItemAdapter::class;

    /** @var class-string<LineItemFulfillmentStatusAdapter> the line item fulfillment status adapter */
    protected $lineItemFulfillmentStatusAdapter = LineItemFulfillmentStatusAdapter::class;

    /** @var class-string<OrderFulfillmentStatusAdapter> the order's fulfillment status adapter */
    protected $orderFulfillmentStatusAdapter = OrderFulfillmentStatusAdapter::class;

    /**
     * Converts a source WooCommerce Order to a core native order.
     *
     * @return CoreOrder
     * @throws AdapterException
     */
    public function convertFromSource() : Order
    {
        /** @var CoreOrder $order */
        $order = parent::convertFromSource();

        $order->setOriginatingChannelId($this->source->get_meta(static::ORIGINATING_CHANNEL_ID_META_KEY));

        $order->setNeedsPayment(TypeHelper::bool($this->source->needs_payment(), false));
        $order->setCheckoutPaymentUrl(TypeHelper::string($this->source->get_checkout_payment_url(), ''));

        $this->convertCustomerNoteFromSource($order);
        $this->convertPaymentDataFromSource($order);
        $this->convertMarketplacesDataFromSource($order);

        // Cart ID
        $this->convertCartIdFromSource($order);

        // fulfillment status
        $this->convertFulfillmentStatusFromSource($order);
        $this->convertLineItemsFulfillmentStatusFromSource($order);

        return $order;
    }

    /**
     * Converts a core native order to a WooCommerce source order object.
     *
     * @param CoreOrder|null $order
     * @return WC_Order
     * @throws AdapterException
     */
    public function convertToSource($order = null) : WC_Order
    {
        if ($order) {
            $this->source->update_meta_data(static::ORIGINATING_CHANNEL_ID_META_KEY, $order->getOriginatingChannelId()); /* @phpstan-ignore-line */
            $this->convertCartIdToSource($order);
            $this->convertCustomerNoteToSource($order);
            $this->convertFulfillmentStatusToSource($order);
        }

        try {
            if ($order) {
                if ($emailAddress = $order->getEmailAddress()) {
                    $this->source->set_billing_email($emailAddress);
                }
            }
        } catch (Exception $exception) {
            throw new AdapterException('Could not adapt native order email address to source order billing email address.', $exception);
        }

        $this->convertMarketplacesDataToSource($this->source, $order);

        parent::convertToSource($order);

        return $this->source;
    }

    /**
     * Sets the cart ID from the given order, if it exists.
     *
     * @param CoreOrder $order
     *
     * @return void
     */
    protected function convertCartIdToSource(CoreOrder $order) : void
    {
        if ($cartId = $order->getCartId()) {
            $this->source = WooOrderCartIdProvider::getNewInstance()->setCartId($this->source, $cartId);
        }
    }

    /**
     * Sets the cart ID from the given order, if it exists.
     *
     * @param CoreOrder $order
     *
     * @return void
     */
    protected function convertCartIdFromSource(CoreOrder $order) : void
    {
        if ($cartId = WooOrderCartIdProvider::getNewInstance()->getCartId($this->source)) {
            $order->setCartId($cartId);
        }
    }

    /**
     * Sets the customer note on the given Order instance if a customer note is available in the source.
     */
    protected function convertCustomerNoteFromSource(CoreOrder $order) : void
    {
        if ($customerNote = $this->source->get_customer_note()) {
            $order->setCustomerNote($customerNote);
        }
    }

    /**
     * Converts payment information from a WC Order object to a core order instance.
     *
     * @param CoreOrder $order
     */
    protected function convertPaymentDataFromSource(CoreOrder $order) : void
    {
        if ($emailAddress = $this->source->get_billing_email()) {
            $order->setEmailAddress($emailAddress);
        }

        if ($this->isSourceOrderCaptured()) {
            $order->setCaptured(true);
        } elseif ($this->isOrderReadyForCapture($order)) {
            $order->setReadyForCapture(true);
        }

        if ($remoteId = $this->source->get_meta('_poynt_order_remoteId')) {
            $order->setRemoteId($remoteId);
        }

        if ($createdVia = $this->source->get_created_via()) {
            $order->setSource((string) $createdVia);
        }

        // Order amounts
        $order->setDiscountAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_discount()));
    }

    /**
     * Determines whether the source order can be considered as captured.
     */
    protected function isSourceOrderCaptured() : bool
    {
        if ('yes' === $this->source->get_meta('_mwc_payments_is_captured')) {
            return true;
        }

        return $this->isSourceOrderPaidByAnotherProvider();
    }

    /**
     * Determines whether the source order was paid by provider other than Poynt.
     */
    protected function isSourceOrderPaidByAnotherProvider() : bool
    {
        // we pass 'edit' as the context parameter for get_date_paid() to prevent the `woocommerce_payment_complete_order_status`
        // filter from being triggered for orders that haven't been saved
        // https://godaddy-corp.atlassian.net/browse/MWC-13191
        return 'poynt' !== $this->source->get_meta('_mwc_transaction_provider_name')
            && ! empty($this->source->get_date_paid('edit'))
            && ! empty($this->source->get_transaction_id());
    }

    /**
     * Converts Marketplaces data from a WooCommerce order object to a core order instance.
     *
     * @param CoreOrder $order
     * @return void
     */
    protected function convertMarketplacesDataFromSource(CoreOrder $order) : void
    {
        if ($channelName = $this->source->get_meta(static::MARKETPLACES_CHANNEL_NAME_META_KEY)) {
            $order->setMarketplacesChannelName($channelName);
        }

        if ($channelType = $this->source->get_meta(static::MARKETPLACES_CHANNEL_TYPE_META_KEY)) {
            $order->setMarketplacesChannelType($channelType);
        }

        if ($channelUuid = $this->source->get_meta(static::MARKETPLACES_CHANNEL_UUID_META_KEY)) {
            $order->setMarketplacesChannelUuid($channelUuid);
        }

        if ($orderStatus = $this->source->get_meta(static::MARKETPLACES_ORDER_STATUS)) {
            $order->setMarketplacesStatus($orderStatus);
        }

        if ($displayOrderNumber = $this->source->get_meta(static::MARKETPLACES_DISPLAY_ORDER_NUMBER_META_KEY)) {
            $order->setMarketplacesDisplayOrderNumber($displayOrderNumber);
        }

        if ($internalOrderNumber = $this->source->get_meta(static::MARKETPLACES_INTERNAL_ORDER_NUMBER_META_KEY)) {
            $order->setMarketplacesInternalOrderNumber($internalOrderNumber);
        }

        if ($channelOrderReference = $this->source->get_meta(static::MARKETPLACES_CHANNEL_ORDER_REFERENCE_META_KEY)) {
            $order->setMarketplacesChannelOrderReference($channelOrderReference);
        }
    }

    /**
     * Convert the order's fulfillment status.
     *
     * @param CoreOrder $order
     * @return void
     * @throws AdapterException
     */
    protected function convertFulfillmentStatusFromSource(CoreOrder $order) : void
    {
        try {
            $this->orderFulfillmentStatusAdapter::getNewInstance($this->source)->convertFromSource($order);
        } catch (Exception $exception) {
            throw new AdapterException('Could not adapt fulfillment data.', $exception);
        }
    }

    /**
     * Converts Marketplaces data from a native order object to WooCommerce order metadata.
     *
     * @param WC_Order $wcOrder
     * @param null|CoreOrder $order
     * @return void
     */
    protected function convertMarketplacesDataToSource(WC_Order $wcOrder, ?CoreOrder $order = null) : void
    {
        if (! $order) {
            return;
        }

        $marketplacesMetaData = [
            static::MARKETPLACES_CHANNEL_NAME_META_KEY            => $order->getMarketplacesChannelName(),
            static::MARKETPLACES_CHANNEL_TYPE_META_KEY            => $order->getMarketplacesChannelType(),
            static::MARKETPLACES_CHANNEL_UUID_META_KEY            => $order->getMarketplacesChannelUuid(),
            static::MARKETPLACES_ORDER_STATUS                     => $order->getMarketplacesStatus(),
            static::MARKETPLACES_DISPLAY_ORDER_NUMBER_META_KEY    => $order->getMarketplacesDisplayOrderNumber(),
            static::MARKETPLACES_INTERNAL_ORDER_NUMBER_META_KEY   => $order->getMarketplacesInternalOrderNumber(),
            static::MARKETPLACES_CHANNEL_ORDER_REFERENCE_META_KEY => $order->getMarketplacesChannelOrderReference(),
        ];

        foreach ($marketplacesMetaData as $metaKey => $metaValue) {
            if (null !== $metaValue) {
                $wcOrder->update_meta_data($metaKey, $metaValue);
            }
        }
    }

    /**
     * Determines whether the order is ready to be captured.
     *
     * TODO: remove status classes from mwc-payments package {@wvega 2021-05-31}.
     *
     * @param Order $order
     * @return bool
     */
    protected function isOrderReadyForCapture(Order $order) : bool
    {
        if (! $providerName = $this->source->get_meta('_mwc_transaction_provider_name')) {
            return false;
        }

        if (! $this->source->get_meta('_'.$providerName.'_payment_remoteId')) {
            return false;
        }

        // @TODO: something I don't like about this method: these order status checks imply too much knowledge / dependency on the WC admin. I think the status checks should be done "higher up" near the UI layer, since really these are determining whether to render a WC admin button or not {JS - 2021-10-21}
        // @TODO: something I don't like about this method: 'isOrderReadyForCapture' is assuming an action (capture) rather than returning a state (open authorization). An authorization can be captured or can be voided, so a better method name would probably be something like 'hasOpenAuthorization' or something to that effect, and the calling code can determine what to do with that state {JS - 2021-10-21}
        $orderStatus = $order->getStatus();

        if ($orderStatus instanceof CheckoutDraftOrderStatus) {
            return false;
        }

        if ($orderStatus instanceof CancelledOrderStatus) {
            return false;
        }

        if ($orderStatus instanceof RefundedOrderStatus) {
            return false;
        }

        if ($orderStatus instanceof FailedOrderStatus) {
            return false;
        }

        return true;
    }

    /**
     * Converts an order amount from source.
     *
     * @param float $amount
     * @return CurrencyAmount
     */
    protected function convertCurrencyAmountFromSource(float $amount) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter($amount, (string) $this->source->get_currency()))->convertFromSource();
    }

    /**
     * @param Order $order
     * @return void
     * @throws AdapterException
     */
    protected function convertLineItemsFulfillmentStatusFromSource(Order $order) : void
    {
        try {
            $items = [];
            $lineItems = $order->getLineItems();

            foreach ($lineItems as $lineItem) {
                $sourceLineItem = new WC_Order_Item_Product($lineItem->getId());
                $items[] = $this->lineItemFulfillmentStatusAdapter::getNewInstance($sourceLineItem)->convertFromSource($lineItem);
            }

            $order->setLineItems($items);
        } catch (Exception $exception) {
            throw new AdapterException('Unable to adapt line items.', $exception);
        }
    }

    /**
     * Sets the customer note on the source object.
     *
     * @throws AdapterException
     */
    protected function convertCustomerNoteToSource(CoreOrder $order) : void
    {
        try {
            $this->source->set_customer_note((string) $order->getCustomerNote());
        } catch (Exception $exception) {
            throw AdapterException::getNewInstance('Could not set the customer note on the source order.', $exception);
        }
    }

    /**
     * Sets the fulfillment status of the given order model using the information in the source {@see WC_Order} instance.
     */
    protected function convertFulfillmentStatusToSource(CoreOrder $order) : void
    {
        $this->orderFulfillmentStatusAdapter::getNewInstance($this->source)->convertToSource($order);
    }
}

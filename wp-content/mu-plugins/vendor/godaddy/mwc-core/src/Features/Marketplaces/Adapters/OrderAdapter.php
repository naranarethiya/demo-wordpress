<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CancelledOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CompletedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\PendingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\ProcessingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\RefundedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\TaxItem;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\OrderWebhookSubscriber;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Traits\ConvertsMarketplacesAmountTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidProductException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\PartiallyFulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\UnfulfilledFulfillmentStatus;

/**
 * Adapts data from a GDM webhook to a native core order object.
 *
 * @method static static getNewInstance(array $order)
 */
class OrderAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;
    use ConvertsMarketplacesAmountTrait;

    /** @var array Order data from the webhook */
    protected $source;

    /**
     * OrderAdapter constructor.
     *
     * @param array<mixed> $order Order data from the webhook payload.
     */
    public function __construct(array $order)
    {
        $this->source = $order;
    }

    /**
     * Gets a new order object.
     *
     * Setting order ID to 0 will ensure the order can be saved later as a new order in WooCommerce.
     * @see OrderWebhookSubscriber::handlePayload()
     *
     * @return Order
     */
    private function getNewOrder() : Order
    {
        /* @phpstan-ignore-next-line */
        return Order::getNewInstance()->setId(0);
    }

    /**
     * Converts a source order from a GDM payload to a core native order.
     *
     * @return Order
     * @throws AdapterException|InvalidProductException
     */
    public function convertFromSource() : Order
    {
        $order = $this->getNewOrder();

        // Statuses
        $sourceStatus = ArrayHelper::get($this->source, 'sb_status', '');
        $order
            ->setStatus($this->convertStatusFromSource($sourceStatus))
            ->setMarketplacesStatus($sourceStatus);

        // Dates
        try {
            if ($createdAt = ArrayHelper::get($this->source, 'ordered_at')) {
                $order->setCreatedAt(new DateTime($createdAt));
            }
        } catch (Exception $exception) {
            throw new AdapterException($exception->getMessage(), $exception);
        }

        // Customer information.
        $email = ArrayHelper::get($this->source, 'billing_email');
        if ($email && is_email($email)) {
            $order->setEmailAddress($email);
        }

        // the order data does not come from a browser request and we don't have an IP, but a value is required
        $order->setCustomerIpAddress('0.0.0.0');

        // Addresses
        $order->setBillingAddress($this->convertBillingAddressFromSource())
            ->setShippingAddress($this->convertShippingAddressFromSource());

        // Order items.
        $order->setLineItems($this->convertOrderItemsFromSource());

        // Tax items.
        $taxAmount = $this->parseAndConvertAmountFromSource('tax');
        $taxItem = $this->convertTaxItemFromSource($taxAmount);

        if ($taxItem) {
            $order->setTaxItems($taxItem);
        }

        // Fulfillment.
        $order->setFulfillmentStatus($this->convertFulfillmentStatusFromSource());

        // Customer note.
        if ($customerNote = TypeHelper::string(ArrayHelper::get($this->source, 'customer_notes'), '')) {
            $order->setCustomerNote($customerNote);
        }

        // Order amounts.
        $order->setLineAmount($this->parseAndConvertAmountFromSource('subtotal'))
            ->setDiscountAmount($this->parseAndConvertAmountFromSource('discount'))
            ->setShippingAmount($this->parseAndConvertAmountFromSource('shipping_cost'))
            ->setTaxAmount($taxAmount)
            ->setTotalAmount($this->parseAndConvertAmountFromSource('total'));

        // Marketplaces data
        if ($marketplacesInternalOrderNumber = ArrayHelper::get($this->source, 'sb_order_seq')) {
            /* @phpstan-ignore-next-line */
            $order->setMarketplacesInternalOrderNumber((string) $marketplacesInternalOrderNumber);
        }
        if ($marketplacesDisplayOrderNumber = ArrayHelper::get($this->source, 'display_ref')) {
            $order->setMarketplacesDisplayOrderNumber(TypeHelper::string($marketplacesDisplayOrderNumber, ''));
        }
        if ($marketplacesChannelOrderReference = ArrayHelper::get($this->source, 'order_id')) {
            /* @phpstan-ignore-next-line */
            $order->setMarketplacesChannelOrderReference((string) $marketplacesChannelOrderReference);
        }
        if ($marketplacesChannelUuid = ArrayHelper::get($this->source, 'channel_uuid')) {
            $order->setMarketplacesChannelUuid(TypeHelper::string($marketplacesChannelUuid, ''));
        }
        if ($commerceChannelUuid = ArrayHelper::get($this->source, 'channel_service_channel_id')) {
            $order->setOriginatingChannelId(TypeHelper::string($commerceChannelUuid, ''));
        }
        if ($marketplacesChannelName = ArrayHelper::get($this->source, 'channel_name')) {
            $order->setMarketplacesChannelName(TypeHelper::string($marketplacesChannelName, ''));
        }
        if ($marketplacesChannelType = ArrayHelper::get($this->source, 'channel_type_display_name')) {
            $order->setMarketplacesChannelType(strtolower(TypeHelper::string($marketplacesChannelType, '')));
        }

        return $order;
    }

    /**
     * Converts the GDM source key to a native OrderStatusContract interface.
     *
     * @param string $sourceStatus
     *
     * @return OrderStatusContract
     */
    protected function convertStatusFromSource(string $sourceStatus) : OrderStatusContract
    {
        // Order status relationships. Key is what we'll find in the source, value is the `OrderStatusContract` class name.
        $statuses = [
            'open'           => ProcessingOrderStatus::class, // open orders in GDM are paid and ready to ship
            'shipped'        => CompletedOrderStatus::class, // WooCommerce does not have statuses pertaining fulfillment although there might be a subtle overlap with both processing and completed here
            'completed'      => CompletedOrderStatus::class,
            'return_pending' => CompletedOrderStatus::class, // in GDM this status implies that the customer has requested a refund, but the item has not been returned or refunded yet
            'returned'       => RefundedOrderStatus::class, // this assumes that the order has been refunded partially or totally
            'canceled'       => CancelledOrderStatus::class,
        ];

        // we ought to set a status even if for some reason there is no match from GDM and we can't assume payment
        $statusClass = ArrayHelper::get($statuses, $sourceStatus, PendingOrderStatus::class);

        /* @phpstan-ignore-next-line */
        return new $statusClass();
    }

    /**
     * Converts the GDM billing address to a native Address model.
     *
     * @return Address
     */
    protected function convertBillingAddressFromSource() : Address
    {
        $addressData = [
            'company'    => ArrayHelper::get($this->source, 'billing_company'),
            'first_name' => ArrayHelper::get($this->source, 'billing_contact_name'),
            'address_1'  => ArrayHelper::get($this->source, 'billing_address_1'),
            'address_2'  => ArrayHelper::get($this->source, 'billing_address_2'),
            'city'       => ArrayHelper::get($this->source, 'billing_city'),
            'state'      => ArrayHelper::get($this->source, 'billing_state_region'),
            'postcode'   => ArrayHelper::get($this->source, 'billing_postal_code'),
            'country'    => ArrayHelper::get($this->source, 'billing_country_code'),
            'phone'      => ArrayHelper::get($this->source, 'billing_phone_number'),
        ];

        return (new AddressAdapter(array_filter($addressData)))->convertFromSource();
    }

    /**
     * Converts the GDM shipping address to a native Address model.
     *
     * @return Address
     */
    protected function convertShippingAddressFromSource() : Address
    {
        $addressData = [
            'company'    => ArrayHelper::get($this->source, 'shipping_company_name'),
            'first_name' => ArrayHelper::get($this->source, 'shipping_contact_name'),
            'address_1'  => ArrayHelper::get($this->source, 'shipping_address_1'),
            'address_2'  => ArrayHelper::get($this->source, 'shipping_address_2'),
            'city'       => ArrayHelper::get($this->source, 'shipping_city'),
            'state'      => ArrayHelper::get($this->source, 'shipping_state_region'),
            'postcode'   => ArrayHelper::get($this->source, 'shipping_postal_code'),
            'country'    => ArrayHelper::get($this->source, 'shipping_country_code'),
            'phone'      => ArrayHelper::get($this->source, 'shipping_phone_number'),
        ];

        return (new AddressAdapter(array_filter($addressData)))->convertFromSource();
    }

    /**
     * Converts the GDM order items into native order items counterparts.
     *
     * @return LineItem[]
     * @throws InvalidProductException
     */
    protected function convertOrderItemsFromSource() : array
    {
        $lineItems = ArrayHelper::get($this->source, 'items');

        $items = [];

        if (ArrayHelper::accessible($lineItems)) {
            foreach ($lineItems as $lineItem) {
                $items[] = $this->convertOrderItemFromSource($lineItem);
            }
        }

        return $items;
    }

    /**
     * Builds a TaxItem object from the payload, if there's tax on the GDM order.
     *
     * @return TaxItem[]
     */
    protected function convertTaxItemFromSource(CurrencyAmount $taxAmount) : array
    {
        if ($taxAmount->getAmount() === 0) {
            return [];
        }

        $taxItem = TaxItem::getNewInstance()
            ->setId(0)
            ->setName(__('Tax', 'mwc-core'))
            ->setLabel(__('Tax', 'mwc-core'))
            ->setRate(0.00) // @TODO this is set to 0.00 for now as we don't know the exact tax rate. Setting it to 0 doesn't seem to have any impact and is not displayed anywhere. But we should circle back and investigate if it's worth attempting to calculate the rate. {agibson 2023-01-26}
            ->setTotalAmount($taxAmount);

        return [$taxItem];
    }

    /**
     * Converts a source line item into a native LineItem object.
     *
     * @param array<string, mixed> $item single line item from the source
     * @return LineItem
     * @throws InvalidProductException
     */
    protected function convertOrderItemFromSource(array $item) : LineItem
    {
        return LineItemAdapter::getNewInstance($item)->convertFromSource();
    }

    /**
     * Converts the GDM fulfillment status into a native FulfillmentStatusContract.
     *
     * @return FulfillmentStatusContract
     */
    protected function convertFulfillmentStatusFromSource() : FulfillmentStatusContract
    {
        // Fulfillment status relationships. Key is what we'll find in the source, value is the `FulfillmentStatusContract` class name.
        $nativeStatuses = [
            'all'     => FulfilledFulfillmentStatus::class,
            'partial' => PartiallyFulfilledFulfillmentStatus::class,
            'none'    => UnfulfilledFulfillmentStatus::class,
        ];

        $statusName = TypeHelper::string(ArrayHelper::get($this->source, 'shipment_status'), '');
        $statusClass = TypeHelper::string(ArrayHelper::get($nativeStatuses, $statusName ?: ''), '');

        if (empty($statusClass) || ! class_exists($statusClass)) {
            return new UnfulfilledFulfillmentStatus();
        }

        /* @phpstan-ignore-next-line */
        return new $statusClass();
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // Not implemented.
        return [];
    }
}

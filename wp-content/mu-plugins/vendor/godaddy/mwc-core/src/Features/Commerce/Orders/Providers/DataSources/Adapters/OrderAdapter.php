<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringRemoteIdentifierTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasStoreIdentifierContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItem as LineItemDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Note as NoteDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Order as OrderDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderContext;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\Factories\OrderContextAdapterFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\DateTimeAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasStoreIdentifierTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class OrderAdapter implements DataObjectAdapterContract, HasStoreIdentifierContract
{
    use HasStoreIdentifierTrait;
    use HasStringRemoteIdentifierTrait;

    protected BillingInfoAdapter $billingInfoAdapter;

    protected OrderContextAdapterFactory $orderContextAdapterFactory;

    protected CustomerRemoteIdAdapter $customerRemoteIdAdapter;

    protected LineItemAdapter $lineItemAdapter;

    protected OrderStatusesAdapter $orderStatusesAdapter;

    protected OrderTotalsAdapter $orderTotalsAdapter;

    protected DateTimeAdapter $dateTimeAdapter;

    protected NoteAdapter $noteAdapter;

    public function __construct(
        BillingInfoAdapter $billingInfoAdapter,
        OrderContextAdapterFactory $orderContextAdapter,
        CustomerRemoteIdAdapter $customerRemoteIdAdapter,
        LineItemAdapter $lineItemAdapter,
        NoteAdapter $noteAdapter,
        OrderStatusesAdapter $orderStatusesAdapter,
        OrderTotalsAdapter $orderTotalsAdapter,
        DateTimeAdapter $dateTimeAdapter
    ) {
        $this->billingInfoAdapter = $billingInfoAdapter;
        $this->orderContextAdapterFactory = $orderContextAdapter;
        $this->customerRemoteIdAdapter = $customerRemoteIdAdapter;
        $this->lineItemAdapter = $lineItemAdapter;
        $this->noteAdapter = $noteAdapter;
        $this->orderStatusesAdapter = $orderStatusesAdapter;
        $this->orderTotalsAdapter = $orderTotalsAdapter;
        $this->dateTimeAdapter = $dateTimeAdapter;
    }

    /**
     * Converts from the Order data source format.
     *
     * @param OrderDataObject $source
     * @return Order
     */
    public function convertFromSource($source)
    {
        $order = Order::getNewInstance();

        $this->orderStatusesAdapter->convertFromSource($source->statuses, $order);

        return $order;
    }

    /**
     * {@inheritDoc}
     *
     * @param Order $target
     * @return OrderDataObject
     */
    public function convertToSource($target)
    {
        return new OrderDataObject([
            'billing'       => $this->billingInfoAdapter->convertToSource($target),
            'cartId'        => $this->getCartId($target),
            'context'       => $this->getContextFromOrder($target),
            'customerId'    => $this->customerRemoteIdAdapter->convertToSource($target),
            'id'            => $this->getOrderId(),
            'lineItems'     => $this->convertLineItemsToSource($target),
            'notes'         => $this->convertNotesToSource($target),
            'number'        => $this->convertNumberToSource($target),
            'numberDisplay' => $this->getNumberDisplay($target),
            'processedAt'   => $this->getProcessedAtFromOrder($target),
            'statuses'      => $this->orderStatusesAdapter->convertToSource($target),
            'totals'        => $this->orderTotalsAdapter->convertToSource($target),
        ]);
    }

    protected function getContextFromOrder(Order $order) : OrderContext
    {
        $adapter = $this->orderContextAdapterFactory->getAdapterFromTarget($order)->setStoreId($this->getStoreId());

        return $adapter->convertToSource($order);
    }

    /**
     * Converts order's {@see LineItem} to Commerce's {@see LineItemDataObject}.
     *
     * @param Order $order
     * @return LineItemDataObject[]
     */
    protected function convertLineItemsToSource(Order $order) : array
    {
        $this->lineItemAdapter->setOrder($order);

        return array_map(
            fn (LineItem $lineItem) => $this->lineItemAdapter->convertToSource($lineItem),
            $order->getLineItems()
        );
    }

    /**
     * Converts order's notes to Commerce {@see NoteDataObject}.
     * @param Order $order
     *
     * @return NoteDataObject[]
     */
    protected function convertNotesToSource(Order $order) : array
    {
        return array_map([$this->noteAdapter, 'convertToSource'], $order->getNotes());
    }

    /**
     * Gets adapted processed at timestamp from given order.
     *
     * @param Order $order
     * @return non-empty-string
     */
    protected function getProcessedAtFromOrder(Order $order) : string
    {
        return $this->dateTimeAdapter->convertToSourceOrNow($order->getCreatedAt());
    }

    /**
     * @return non-empty-string|null
     */
    protected function getOrderId() : ?string
    {
        return $this->nonEmptyStringOrNull($this->getRemoteId());
    }

    /**
     * Returns the given value if it is a non-empty string or null otherwise.
     *
     * @param mixed $value
     * @return non-empty-string|null
     */
    protected function nonEmptyStringOrNull($value) : ?string
    {
        return TypeHelper::string($value, '') ?: null;
    }

    /**
     * Gets the Cart ID of the order model.
     *
     * @return non-empty-string|null
     */
    protected function getCartId(Order $order) : ?string
    {
        return $this->nonEmptyStringOrNull($order->getCartId());
    }

    /**
     * Convert order number value for commerce order, with cartId as fallback value.
     *
     * @param Order $order
     *
     * @return non-empty-string|null
     */
    protected function convertNumberToSource(Order $order) : ?string
    {
        return $this->nonEmptyStringOrNull($order->getNumber() ?: $order->getCartId());
    }

    /**
     * Get order numberDisplay value for commerce order. If it is cartId, trim it.
     *
     * @param Order $order
     *
     * @return non-empty-string|null
     */
    protected function getNumberDisplay(Order $order) : ?string
    {
        $number = $this->convertNumberToSource($order);

        if ($number && $order->getCartId() === $number) {
            return $this->nonEmptyStringOrNull(StringHelper::substring($number, 0, 8));
        }

        return $number;
    }
}

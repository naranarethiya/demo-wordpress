<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CancelledOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CompletedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\FailedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\HeldOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\PendingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\ProcessingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\RefundedOrderStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\PaymentStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\Status;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderStatuses;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Converts a Commerce {@see OrderStatuses} data object into a native order status {@see OrderStatusContract}.
 */
class OrderStatusesAdapter implements DataObjectAdapterContract
{
    /** @var OrderFulfillmentStatusAdapter */
    protected OrderFulfillmentStatusAdapter $orderFulfillmentStatusAdapter;

    /**
     * Constructor.
     *
     * @param OrderFulfillmentStatusAdapter $orderFulfillmentStatusAdapter
     */
    public function __construct(OrderFulfillmentStatusAdapter $orderFulfillmentStatusAdapter)
    {
        $this->orderFulfillmentStatusAdapter = $orderFulfillmentStatusAdapter;
    }

    /**
     * Converts a Commerce {@see OrderStatuses} data object into a native order status {@see OrderStatusContract}.
     *
     * @param OrderStatuses $source
     */
    public function convertFromSource($source, ?Order $order = null) : Order
    {
        $order ??= new Order();

        return $order->setStatus($this->convertOrderStatusFromSource($source))
            ->setFulfillmentStatus(
                $this->orderFulfillmentStatusAdapter->convertFromSource($source->fulfillmentStatus)
            );
    }

    /**
     * Converts a native order status {@see OrderStatusContract} into a Commerce {@see OrderStatuses} data object.
     *
     * @param Order $target
     */
    public function convertToSource($target) : OrderStatuses
    {
        return new OrderStatuses([
            'fulfillmentStatus' => $this->orderFulfillmentStatusAdapter->convertToSource($target),
            'paymentStatus'     => $this->mapPaymentStatusToSource($target),
            'status'            => $this->mapOrderStatusToSource($target),
        ]);
    }

    /**
     * Returns the commerce order status mapped from MWC order status.
     *
     * @param Order $order
     * @return Status::*
     */
    protected function mapOrderStatusToSource(Order $order) : string
    {
        if (! $orderStatus = $order->getStatus()) {
            return Status::Open;
        }

        $statusMapping = [
            CompletedOrderStatus::class => Status::Completed,
            CancelledOrderStatus::class => Status::Canceled,
            RefundedOrderStatus::class  => Status::Canceled,
        ];

        return Status::tryFrom(
            TypeHelper::string(
                ArrayHelper::get($statusMapping, get_class($orderStatus)),
                ''
            )
        ) ?? Status::Open;
    }

    /**
     * Returns the commerce order payment status mapped from MWC order status.
     *
     * @param Order $order
     * @return PaymentStatus::*
     */
    protected function mapPaymentStatusToSource(Order $order) : string
    {
        if (! $orderStatus = $order->getStatus()) {
            return PaymentStatus::None;
        }

        $statusMapping = [
            PendingOrderStatus::class    => PaymentStatus::None,
            ProcessingOrderStatus::class => PaymentStatus::Paid,
            HeldOrderStatus::class       => PaymentStatus::Pending,
            CompletedOrderStatus::class  => PaymentStatus::Paid,
            CancelledOrderStatus::class  => PaymentStatus::Canceled,
            RefundedOrderStatus::class   => PaymentStatus::Refunded,
            FailedOrderStatus::class     => PaymentStatus::Declined,
        ];

        return PaymentStatus::tryFrom(
            TypeHelper::string(
                ArrayHelper::get($statusMapping, get_class($orderStatus)),
                ''
            )
        ) ?? PaymentStatus::None;
    }

    /**
     * Converts a Commerce {@see OrderStatuses} data object into a native order status {@see OrderStatusContract}.
     *
     * @param OrderStatuses $source
     */
    protected function convertOrderStatusFromSource(OrderStatuses $source) : OrderStatusContract
    {
        $orderStatus = $source->status;
        $paymentStatus = $source->paymentStatus;

        if (PaymentStatus::Refunded === $paymentStatus &&
            in_array($orderStatus, [Status::Completed, Status::Canceled], true)) {
            return new RefundedOrderStatus();
        }

        if (Status::Canceled === $orderStatus &&
            ! in_array($paymentStatus, [PaymentStatus::None, PaymentStatus::Refunded], true)) {
            return new CancelledOrderStatus();
        }

        if (Status::Completed === $orderStatus) {
            return new CompletedOrderStatus();
        }

        if (Status::Open === $orderStatus) {
            if (PaymentStatus::Declined === $paymentStatus) {
                return new FailedOrderStatus();
            }

            if (PaymentStatus::Pending === $paymentStatus) {
                return new HeldOrderStatus();
            }

            if (PaymentStatus::None === $paymentStatus) {
                return new PendingOrderStatus();
            }
        }

        return new ProcessingOrderStatus();
    }
}

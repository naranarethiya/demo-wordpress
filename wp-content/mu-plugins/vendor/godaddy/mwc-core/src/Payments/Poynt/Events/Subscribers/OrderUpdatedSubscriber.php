<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

class OrderUpdatedSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        /* @var ModelEvent $event */

        /* @phpstan-ignore-next-line */
        $this->maybePushTransactionToPoynt($event);
    }

    /**
     * Determines whether the event should be handled.
     *
     * @param EventContract $event
     * @return bool
     * @throws Exception
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        if (
            ! Configuration::get('features.bopit', false) ||
            ! $event instanceof ModelEvent ||
            'order' !== $event->getResource() ||
            'update' !== $event->getAction()
        ) {
            return false;
        }

        return true;
    }

    /**
     * May send a transaction to Poynt.
     *
     * @param ModelEvent $event
     * @throws Exception
     */
    protected function maybePushTransactionToPoynt(ModelEvent $event) : void
    {
        $order = $event->getModel();
        if (! $order instanceof Order || ! $orderId = $order->getId()) {
            return;
        }

        if (! $wcOrder = OrdersRepository::get($orderId)) {
            return;
        }

        if (! static::shouldPushSaleTransactionToPoynt($wcOrder, $order)
            && ! static::shouldPushAuthorizationTransactionToPoynt($wcOrder, $order)
            && ! static::shouldPushCaptureTransactionToPoynt($wcOrder, $order)) {
            return;
        }

        PushSyncJob::create([
            'owner'      => 'poynt_order_transaction',
            'batchSize'  => 1,
            'objectType' => 'order',
            'objectIds'  => ArrayHelper::wrap($orderId),
        ]);
    }

    /**
     * Determines if a SALE transaction should be sent to the Poynt API.
     *
     * Used to display orders paid with 3rd party gateways as Paid on the Smart Terminal.
     *
     * @param WC_Order $wcOrder
     * @param Order $order
     * @return bool
     */
    public static function shouldPushSaleTransactionToPoynt(WC_Order $wcOrder, Order $order) : bool
    {
        if (! Poynt::shouldPushOrderDetailsToPoynt($order)) {
            return false;
        }

        // order was not paid
        if (empty($wcOrder->get_transaction_id()) || empty($wcOrder->get_date_paid())) {
            return false;
        }

        // payment transaction was already submitted to Poynt
        if (! empty($wcOrder->get_meta('_poynt_payment_remoteId'))) {
            return false;
        }

        // skip orders paid with a GD gateway or cash
        return ! ArrayHelper::contains(Poynt\Events\Producers\PushOrdersProducer::EXCLUDE_TRANSACTIONS_ON, $wcOrder->get_payment_method());
    }

    /**
     * Determines if an AUTHORIZE transaction should be sent to the Poynt API.
     *
     * Used to display orders paid with 3rd party gateways as Pending on the Smart Terminal.
     *
     * @param WC_Order $wcOrder
     * @param Order $order
     * @return bool
     */
    public static function shouldPushAuthorizationTransactionToPoynt(WC_Order $wcOrder, Order $order) : bool
    {
        if (! Poynt::shouldPushOrderDetailsToPoynt($order)) {
            return false;
        }

        // order was not authorized
        if (empty($wcOrder->get_transaction_id())) {
            return false;
        }

        // order was already paid (should push a SALE transaction instead)
        if (! empty($wcOrder->get_date_paid())) {
            return false;
        }

        // payment transaction was already submitted to Poynt
        if (! empty($wcOrder->get_meta('_poynt_payment_remoteId'))) {
            return false;
        }

        // skip orders paid with a GD gateway or cash
        return ! ArrayHelper::contains(Poynt\Events\Producers\PushOrdersProducer::EXCLUDE_TRANSACTIONS_ON, $wcOrder->get_payment_method());
    }

    /**
     * Determines if a CAPTURE transaction should be sent to the Poynt API.
     *
     * Used to display orders captured with 3rd party gateways as Paid on the Smart Terminal.
     *
     * @param WC_Order $wcOrder
     * @param Order $order
     * @return bool
     */
    public static function shouldPushCaptureTransactionToPoynt(WC_Order $wcOrder, Order $order) : bool
    {
        if (! Poynt::shouldPushOrderDetailsToPoynt($order)) {
            return false;
        }

        // order was not paid
        if (empty($wcOrder->get_transaction_id()) || empty($wcOrder->get_date_paid())) {
            return false;
        }

        // capture transaction was already submitted to Poynt
        if (! empty($wcOrder->get_meta('_poynt_capture_remoteId'))) {
            return false;
        }

        // skip orders paid with a GD gateway or cash
        return ! ArrayHelper::contains(Poynt\Events\Producers\PushOrdersProducer::EXCLUDE_TRANSACTIONS_ON, $wcOrder->get_payment_method());
    }
}

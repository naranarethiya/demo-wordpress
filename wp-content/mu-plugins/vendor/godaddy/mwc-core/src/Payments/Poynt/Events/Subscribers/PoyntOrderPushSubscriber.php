<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PoyntOrderPushSyncJobException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class PoyntOrderPushSubscriber implements SubscriberContract
{
    /** @var bool keep track of whether the create order event has already been processed */
    protected static $hasProcessedEvent = false;

    /**
     * @param EventContract $event
     * @return void
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldSendEvent($event)) {
            return;
        }

        /* @var ModelEvent $event */

        try {
            /* @phpstan-ignore-next-line */
            $this->sendEventToSyncOrderJob($event);
        } catch (Exception $exception) {
            throw new PoyntOrderPushSyncJobException($exception->getMessage(), $exception);
        }
    }

    /**
     * Send the Event to the sync order job.
     *
     * @param ModelEvent $event
     * @return void
     * @throws Exception
     */
    protected function sendEventToSyncOrderJob(ModelEvent $event)
    {
        $order = $event->getModel();

        if ($order instanceof Order && is_numeric($orderId = $order->getId())) {
            PushSyncJob::create([
                'owner'      => 'poynt_order',
                'batchSize'  => 1,
                'objectType' => 'order',
                'objectIds'  => ArrayHelper::wrap($orderId),
            ]);
        }
    }

    /**
     * Determines whether the given event should be sent.
     *
     * @param EventContract $event event object
     *
     * @return bool
     */
    protected function shouldSendEvent(EventContract $event) : bool
    {
        // bail if we've already processed the event (order create events can be fired multiple times, without context)
        if (static::$hasProcessedEvent) {
            return false;
        }

        // ensure this is a "create order" event, as ModelEvent is used for both create and update
        if (! $event instanceof ModelEvent || 'order' !== $event->getResource() || 'create' !== $event->getAction()) {
            return false;
        }

        $order = $event->getModel();

        if (! $order instanceof Order || ! Poynt::shouldPushOrderDetailsToPoynt($order)) {
            return false;
        }

        static::$hasProcessedEvent = true;

        return true;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Channels\Interceptors\FindOrCreateOrderChannelActionInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Exceptions\OrderSubscriberException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Listens to {@see ModelEvent} for order created and updated events.
 */
class OrderChannelSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     */
    public function handle(EventContract $event)
    {
        if (! $this->isValidEvent($event)) {
            return;
        }

        try {
            /** @var ModelEvent $event */
            $order = $this->getOrder($event);

            // at this time we only create channels for Marketplaces orders
            if (! $order->hasMarketplacesChannel() || $order->getOriginatingChannelId()) {
                return;
            }

            $this->scheduleChannelJob($order);
        } catch (Exception $e) {
            // catching exceptions here to prevent unexpected errors during runtime
        }
    }

    /**
     * Determines if this is a valid event we should handle.
     *
     * @param EventContract $event
     * @return bool
     */
    protected function isValidEvent(EventContract $event) : bool
    {
        return $event instanceof ModelEvent && $event->getResource() === 'order';
    }

    /**
     * Gets a core order object from the event data.
     *
     * @param ModelEvent $event
     * @return Order
     * @throws OrderSubscriberException
     */
    protected function getOrder(ModelEvent $event) : Order
    {
        $order = $event->getModel();

        if (! $order instanceof Order) {
            throw new OrderSubscriberException('Event model is not an Order.');
        }

        return $order;
    }

    /**
     * Schedules the job to find-or-create a channel via the MWC API.
     *
     * @param Order $order
     * @return void
     * @throws InvalidScheduleException
     */
    protected function scheduleChannelJob(Order $order) : void
    {
        $job = Schedule::singleAction()
            ->setScheduleAt(new DateTime())
            ->setName(FindOrCreateOrderChannelActionInterceptor::FIND_OR_CREATE_ORDER_CHANNEL_ACTION)
            ->setArguments($order->getId(), $this->makeChannelRequestBodyForOrder($order), 1);

        // it's possible that the job has already been scheduled, since the order events can fire multiple times when an order is created
        if (! $job->isScheduled()) {
            $job->schedule();
        }
    }

    /**
     * Makes the body for a find-or-create channel request for a GDM order.
     *
     * @param Order $order
     * @return array<string, array<string, mixed>>
     */
    protected function makeChannelRequestBodyForOrder(Order $order) : array
    {
        $channelType = TypeHelper::string($order->getMarketplacesChannelType(), '');

        return [
            'find' => [
                'externalChannelId' => $order->getMarketplacesChannelUuid(),
                'type'              => ChannelRepository::getChannelServiceChannelType($channelType),
                'subType'           => "com.godaddy.marketplaces.{$channelType}",
            ],
            'create' => $this->makeCreateChannelRequestArgs($order),
        ];
    }

    /**
     * Builds the arguments for creating a channel.
     *
     * @param Order $order
     * @return array<string, mixed>
     */
    protected function makeCreateChannelRequestArgs(Order $order) : array
    {
        $createArgs = [
            'name' => $order->getMarketplacesChannelName(),
        ];

        try {
            $storeId = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getStoreRepository()->getStoreId();
        } catch (\Exception $e) {
            $storeId = null;
        }

        if ($storeId) {
            $createArgs['registeredStores'] = [
                [
                    'storeId' => $storeId,
                    'status'  => 'ENABLED',
                    'default' => false,
                ],
            ];
        }

        return $createArgs;
    }
}

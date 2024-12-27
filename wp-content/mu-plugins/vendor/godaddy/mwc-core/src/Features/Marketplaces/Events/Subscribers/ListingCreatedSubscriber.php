<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ListingCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\Services\GoogleProductService;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;

/**
 * Listens to {@see ListingCreatedEvent} events.
 */
class ListingCreatedSubscriber implements SubscriberContract
{
    /**
     * Schedules a Google product ID request when a listing is created for a product.
     *
     * @see GoogleProductService::scheduleProductIdRequest()
     *
     * @param ListingCreatedEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(EventContract $event) : void
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        if (! ChannelRepository::isConnected(Channel::TYPE_GOOGLE)) {
            return;
        }

        if ($productId = $event->getProduct()->getId()) {
            GoogleProductService::scheduleProductIdRequest(ArrayHelper::wrap($productId));
        }
    }

    /**
     * Determines if the event should be handled by this subscriber.
     *
     * Ignore events that have already a Google product ID set.
     *
     * @param EventContract $event
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof ListingCreatedEvent && ! $event->getProduct()->getMarketplacesGoogleProductId();
    }
}

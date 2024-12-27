<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ListingDeletedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use WC_Product;

/**
 * Listens to {@see ListingDeletedEvent} events.
 */
class ListingDeletedSubscriber implements SubscriberContract
{
    /**
     * Sets the Google Product ID to null when a listing is deleted for a product.
     *
     * @param ListingDeletedEvent $event
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

        $product = $event->getProduct()->setMarketplacesGoogleProductId(null);

        ProductAdapter::getNewInstance(new WC_Product())->convertToSource($product)->save();
    }

    /**
     * Determines if the event should be handled by this subscriber.
     *
     * Ignore events that don't have a Google product ID set.
     *
     * @param EventContract $event
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof ListingDeletedEvent && null !== $event->getProduct()->getMarketplacesGoogleProductId();
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventTransformFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\ListingWebhookPayload;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Services\ProductListingService;

/**
 * The Marketplaces listing webhook subscriber.
 */
class ListingWebhookSubscriber extends AbstractWebhookSubscriber implements ComponentContract
{
    /** @var string */
    protected string $webhookType = 'listing';

    /**
     * Handles the webhook payload.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return void
     * @throws Exception|SentryException|EventTransformFailedException
     */
    public function handlePayload(AbstractWebhookReceivedEvent $event) : void
    {
        /** @var ListingWebhookPayload|null $webhookPayload */
        $webhookPayload = $this->getWebhookPayload($event);

        if (! $webhookPayload) {
            return;
        }

        $service = ProductListingService::getNewInstance($webhookPayload);

        if ('webhook_listing_deleted' === $webhookPayload->getEventType()) {
            $service->deleteListing();
        } else {
            $service->saveListing();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load() : void
    {
        // not implemented
    }
}

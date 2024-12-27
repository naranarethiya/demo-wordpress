<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\GoogleMarketplacesAnalyticsProvider;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\GoogleAdsTrackingWebhookPayload;

/**
 * Google Ads tracking webhook subscriber class.
 */
class GoogleAdsTrackingWebhookSubscriber extends AbstractWebhookSubscriber implements ComponentContract
{
    /** @var string */
    protected string $webhookType = 'googleTracking';

    /**
     * Handles the webhook payload event.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return void
     * @throws SentryException
     */
    public function handlePayload(AbstractWebhookReceivedEvent $event) : void
    {
        /** @var GoogleAdsTrackingWebhookPayload|null $webhookPayload */
        $webhookPayload = $this->getWebhookPayload($event);

        if (! $webhookPayload) {
            return;
        }

        GoogleMarketplacesAnalyticsProvider::getNewInstance()
            ->updateTrackingId(TypeHelper::string($webhookPayload->getTrackingId(), ''))
            ->updateConversionLabel(TypeHelper::string($webhookPayload->getConversionLabel(), ''));
    }

    /**
     * {@inheritDoc}
     */
    public function load() : void
    {
        // not implemented
    }
}

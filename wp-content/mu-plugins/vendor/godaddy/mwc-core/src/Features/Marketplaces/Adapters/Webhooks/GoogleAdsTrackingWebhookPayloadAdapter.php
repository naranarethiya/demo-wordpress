<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\GoogleAdsTrackingWebhookPayload;

/**
 * Adapts data from a GDM channel webhook payload to a native {@see GoogleAdsTrackingWebhookPayload} object.
 */
class GoogleAdsTrackingWebhookPayloadAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> Google ads tracking data from the webhook */
    protected array $source;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $decodedWebhookPayload
     */
    public function __construct(array $decodedWebhookPayload)
    {
        $this->source = $decodedWebhookPayload;
    }

    /**
     * Converts the decoded payload information into a GoogleAdsTrackingWebhookPayload object.
     *
     * @return GoogleAdsTrackingWebhookPayload
     */
    public function convertFromSource() : GoogleAdsTrackingWebhookPayload
    {
        return (new GoogleAdsTrackingWebhookPayload())
            ->setEventType(TypeHelper::string(ArrayHelper::get($this->source, 'event_type'), ''))
            ->setIsExpectedEvent($this->isGoogleAdsTrackingEvent())
            ->setTrackingId(TypeHelper::string(ArrayHelper::get($this->source, 'payload.googleShoppingAdTrackingID'), '') ?: null)
            ->setConversionLabel(TypeHelper::string(ArrayHelper::get($this->source, 'payload.googleShoppingAdConversionLabel'), '') ?: null);
    }

    /**
     * Determines if the webhook received is for a Google ads tracking event.
     *
     * @return bool
     */
    protected function isGoogleAdsTrackingEvent() : bool
    {
        return 'webhook_google_ads_connected' === ArrayHelper::get($this->source, 'event_type');
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

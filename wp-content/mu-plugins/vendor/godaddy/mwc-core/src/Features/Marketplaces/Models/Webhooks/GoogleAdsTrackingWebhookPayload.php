<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks;

/**
 * Google Ads tracking webhook payload class.
 */
class GoogleAdsTrackingWebhookPayload extends AbstractWebhookPayload
{
    /** @var string|null */
    protected ?string $trackingId = null;

    /** @var string|null */
    protected ?string $conversionLabel = null;

    /**
     * Gets the tracking ID.
     *
     * @return string|null
     */
    public function getTrackingId() : ?string
    {
        return $this->trackingId;
    }

    /**
     * Gets the conversion label.
     *
     * @return string|null
     */
    public function getConversionLabel() : ?string
    {
        return $this->conversionLabel;
    }

    /**
     * Sets the tracking ID.
     *
     * @param string|null $value
     * @return $this
     */
    public function setTrackingId(?string $value) : GoogleAdsTrackingWebhookPayload
    {
        $this->trackingId = $value;

        return $this;
    }

    /**
     * Sets the conversion label.
     *
     * @param string|null $value
     * @return $this
     */
    public function setConversionLabel(?string $value) : GoogleAdsTrackingWebhookPayload
    {
        $this->conversionLabel = $value;

        return $this;
    }
}

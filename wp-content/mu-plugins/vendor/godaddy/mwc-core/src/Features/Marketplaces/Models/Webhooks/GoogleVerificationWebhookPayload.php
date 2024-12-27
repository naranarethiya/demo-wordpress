<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks;

/**
 * Object representing webhook information used for Google site verification.
 */
class GoogleVerificationWebhookPayload extends AbstractWebhookPayload
{
    /** @var string|null */
    protected ?string $googleSiteVerificationId;

    /**
     * Gets the Google site verification ID.
     *
     * @return string|null
     */
    public function getGoogleSiteVerificationId() : ?string
    {
        return $this->googleSiteVerificationId;
    }

    /**
     * Sets the Google site verification ID.
     *
     * @param string $value
     * @return $this
     */
    public function setGoogleSiteVerificationId(string $value) : GoogleVerificationWebhookPayload
    {
        $this->googleSiteVerificationId = $value;

        return $this;
    }
}

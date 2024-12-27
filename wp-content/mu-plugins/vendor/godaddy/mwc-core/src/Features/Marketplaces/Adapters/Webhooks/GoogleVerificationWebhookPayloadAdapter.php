<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\GoogleVerificationWebhookPayload;

/**
 * Adapts data from a GDM Google site verification webhook to a native GoogleVerificationWebhookPayload object.
 */
class GoogleVerificationWebhookPayloadAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> Google site verification data from the webhook */
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
     * Converts the decoded payload information into a GoogleVerificationWebhookPayload object.
     *
     * @return GoogleVerificationWebhookPayload
     */
    public function convertFromSource() : GoogleVerificationWebhookPayload
    {
        return (new GoogleVerificationWebhookPayload())
            ->setEventType(TypeHelper::string(ArrayHelper::get($this->source, 'event_type'), ''))
            ->setIsExpectedEvent($this->isGoogleSiteVerification())
            ->setGoogleSiteVerificationId(TypeHelper::string(ArrayHelper::get($this->source, 'payload.googleSiteVerificationId'), ''));
    }

    /**
     * Determines if the webhook received is for a Google site verification.
     *
     * @return bool
     */
    protected function isGoogleSiteVerification() : bool
    {
        return 'webhook_google_verification' === ArrayHelper::get($this->source, 'event_type');
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

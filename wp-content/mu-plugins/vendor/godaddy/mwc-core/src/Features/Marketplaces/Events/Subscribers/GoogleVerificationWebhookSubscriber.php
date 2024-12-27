<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\GoogleVerificationWebhookPayload;

/**
 * Subscriber to the Google Verification webhook.
 *
 * @see GoogleVerificationWebhookPayload
 */
class GoogleVerificationWebhookSubscriber extends AbstractWebhookSubscriber implements ComponentContract
{
    /** @var string */
    const GOOGLE_VERIFICATION_ID_OPTION_KEY = 'mwc_google_site_verification_id';

    /** @var string */
    protected string $webhookType = 'googleVerification';

    /**
     * Maybe saves the Google site verification code to wp_options.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return void
     * @throws SentryException|Exception
     */
    public function handlePayload(AbstractWebhookReceivedEvent $event) : void
    {
        /** @var GoogleVerificationWebhookPayload|null $webhookPayload */
        $webhookPayload = $this->getWebhookPayload($event);

        if (! $webhookPayload) {
            return;
        }

        update_option(self::GOOGLE_VERIFICATION_ID_OPTION_KEY, TypeHelper::string($webhookPayload->getGoogleSiteVerificationId(), ''));
    }

    /**
     * {@inheritDoc}
     */
    public function load() : void
    {
        // not implemented
    }
}

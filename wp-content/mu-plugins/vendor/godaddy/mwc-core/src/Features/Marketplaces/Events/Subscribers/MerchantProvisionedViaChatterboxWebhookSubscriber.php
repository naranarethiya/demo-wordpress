<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\MerchantProvisioningHandler;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\MerchantProvisionedViaChatterboxWebhookPayload;

/**
 * Subscribes to webhooks issued when a merchant is provisioned via chatterbox.
 */
class MerchantProvisionedViaChatterboxWebhookSubscriber extends AbstractWebhookSubscriber implements SubscriberContract
{
    /** @var string */
    protected string $webhookType = 'chatterboxProvisioned';

    /**
     * Issues a request to update the merchant when they are provisioned via chatterbox.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return void
     * @throws SentryException
     */
    public function handlePayload(AbstractWebhookReceivedEvent $event) : void
    {
        /** @var MerchantProvisionedViaChatterboxWebhookPayload|null $webhookPayload */
        $webhookPayload = $this->getWebhookPayload($event);

        if (! $webhookPayload) {
            return;
        }

        MerchantProvisioningHandler::getNewInstance()->maybeSendUpdateMerchantRequest();
    }
}

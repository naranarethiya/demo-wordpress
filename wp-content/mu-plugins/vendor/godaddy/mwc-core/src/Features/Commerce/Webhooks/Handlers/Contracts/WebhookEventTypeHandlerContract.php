<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers\Contracts;

use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;

/**
 * Describes classes that can handle a single webhook event type.
 */
interface WebhookEventTypeHandlerContract
{
    /**
     * Handles the webhook.
     *
     * @param Webhook $webhook
     * @throws WebhookProcessingException
     */
    public function handle(Webhook $webhook) : void;

    /**
     * Determines if the handler should handle the incoming webhook.
     *
     * @param Webhook $webhook
     *
     * @return bool
     * @throws WebhookProcessingException
     */
    public function shouldHandle(Webhook $webhook) : bool;
}

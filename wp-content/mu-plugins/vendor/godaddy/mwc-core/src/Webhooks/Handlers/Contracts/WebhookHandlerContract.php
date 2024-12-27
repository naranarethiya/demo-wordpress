<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Handlers\Contracts;

use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\Handlers\ProcessWebhookJobHandler;

/**
 * Describes classes that handle and process an incoming webhook.
 */
interface WebhookHandlerContract
{
    /**
     * Handles the webhook.
     *
     * NOTE: There is no need to update the webhook's `processed_at` column in the database. This is handled by
     * {@see ProcessWebhookJobHandler::run()}.
     *
     * @param Webhook $webhook
     * @return void
     */
    public function handle(Webhook $webhook) : void;
}

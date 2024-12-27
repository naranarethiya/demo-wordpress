<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers;

use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;

/**
 * Handles `commerce.category.created` webhooks.
 */
class CategoryCreatedWebhookHandler extends AbstractProductWebhookHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle(Webhook $webhook) : void
    {
        // TODO: Implement handle() method.
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers;

use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;

/**
 * Handles `commerce.product.created` webhooks.
 */
class ProductCreatedWebhookHandler extends AbstractProductWebhookHandler
{
    /**
     * {@inheritDoc}
     *
     * @throws WebhookProcessingException
     */
    public function shouldHandle(Webhook $webhook) : bool
    {
        if ($this->localId = $this->getLocalId($webhook)) {
            // product already exists
            return false;
        }

        return parent::shouldHandle($webhook);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Webhook $webhook) : void
    {
        // TODO: Implement handle() method.
    }
}

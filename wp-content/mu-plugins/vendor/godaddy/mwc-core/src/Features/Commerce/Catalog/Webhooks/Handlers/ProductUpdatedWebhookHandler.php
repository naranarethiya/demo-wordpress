<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers;

use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;

/**
 * Handles `commerce.product.updated` webhooks.
 */
class ProductUpdatedWebhookHandler extends AbstractProductWebhookHandler
{
    /**
     * {@inheritDoc}
     *
     * @throws WebhookProcessingException
     */
    public function shouldHandle(Webhook $webhook) : bool
    {
        if (! $this->localId = $this->getLocalId($webhook)) {
            throw new WebhookProcessingException('Local product ID not found for remote product ID: '.$webhook->remoteResourceId);
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

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers\Contracts\WebhookEventTypeHandlerContract;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Abstract base for handlers that process product webhooks.
 */
abstract class AbstractProductWebhookHandler implements WebhookEventTypeHandlerContract
{
    protected ?int $localId;

    protected ProductMapRepository $productMapRepository;

    protected WebhooksRepository $webhooksRepository;

    public function __construct(ProductMapRepository $productMapRepository, WebhooksRepository $webhooksRepository)
    {
        $this->productMapRepository = $productMapRepository;
        $this->webhooksRepository = $webhooksRepository;
    }

    /**
     * Determine if an incoming webhook is stale based on previously received webhook data.
     *
     * @param Webhook $incomingWebhook
     * @return bool
     * @throws WebhookProcessingException
     */
    protected function incomingWebhookIsStale(Webhook $incomingWebhook) : bool
    {
        if (! $incomingWebhook->remoteResourceId) {
            throw new WebhookProcessingException('Product ID not found in the incoming webhook data.');
        }

        $existingWebhookRecord = $this->webhooksRepository->getLatestCompletedWebhookByResourceId($incomingWebhook->remoteResourceId);
        if (! $existingWebhookRecord) {
            return false;
        }

        // Incoming webhook is stale if a newer one has already been proceeded
        return $incomingWebhook->occurredAt <= $existingWebhookRecord->occurredAt;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldHandle(Webhook $webhook) : bool
    {
        if (! $webhook->remoteResourceId) {
            throw new WebhookProcessingException('Product ID not found in webhook data.');
        }

        if (! $webhook->occurredAt) {
            throw new WebhookProcessingException('Webhook timestamp not found in webhook data.');
        }

        if ($this->incomingWebhookIsStale($webhook)) {
            return false;
        }

        return true;
    }

    /**
     * Gets the local product ID for the remoteResourceId in a Webhook.
     *
     * @throws WebhookProcessingException
     */
    protected function getLocalId(Webhook $webhook) : ?int
    {
        if (! $webhook->remoteResourceId) {
            throw new WebhookProcessingException('Product ID not found in webhook data.');
        }

        return $this->productMapRepository->getLocalId($webhook->remoteResourceId);
    }
}

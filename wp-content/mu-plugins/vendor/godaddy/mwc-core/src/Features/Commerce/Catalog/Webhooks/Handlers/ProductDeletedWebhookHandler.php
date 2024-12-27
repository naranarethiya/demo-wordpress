<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\RemoteProductNotFoundHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Handles `commerce.product.deleted` webhooks.
 */
class ProductDeletedWebhookHandler extends AbstractProductWebhookHandler
{
    protected RemoteProductNotFoundHelper $remoteProductNotFoundHelper;

    public function __construct(
        RemoteProductNotFoundHelper $remoteProductNotFoundHelper,
        ProductMapRepository $productMapRepository,
        WebhooksRepository $webhooksRepository
    ) {
        $this->remoteProductNotFoundHelper = $remoteProductNotFoundHelper;

        parent::__construct($productMapRepository, $webhooksRepository);
    }

    /**
     * {@inheritDoc}
     * @throws WebhookProcessingException
     * @phpstan-assert-if-true int $this->localId
     */
    public function shouldHandle(Webhook $webhook) : bool
    {
        if (! $this->localId = $this->getLocalId($webhook)) {
            // nothing to delete
            return false;
        }

        return parent::shouldHandle($webhook);
    }

    /**
     * Handles `commerce.product.deleted` events by also deleting the corresponding local product.
     *
     * @param Webhook $webhook
     * @return void
     * @throws WebhookProcessingException
     */
    public function handle(Webhook $webhook) : void
    {
        if (! $this->shouldHandle($webhook)) {
            return;
        }

        $this->remoteProductNotFoundHelper->handle($this->localId);
    }
}

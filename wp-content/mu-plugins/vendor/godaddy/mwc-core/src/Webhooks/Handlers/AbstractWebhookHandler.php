<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Handlers;

use GoDaddy\WordPress\MWC\Core\Webhooks\Handlers\Contracts\WebhookHandlerContract;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Abstract webhook handler.
 */
abstract class AbstractWebhookHandler implements WebhookHandlerContract
{
    protected WebhooksRepository $webhooksRepository;

    public function __construct(WebhooksRepository $webhooksRepository)
    {
        $this->webhooksRepository = $webhooksRepository;
    }
}

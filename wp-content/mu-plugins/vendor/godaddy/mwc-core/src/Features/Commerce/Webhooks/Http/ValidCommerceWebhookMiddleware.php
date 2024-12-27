<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Http;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Repositories\WebhookSubscriptionRepository;
use GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\AbstractValidWebhookMiddleware;

/**
 * Validates an incoming Commerce webhook.
 */
class ValidCommerceWebhookMiddleware extends AbstractValidWebhookMiddleware
{
    protected CommerceContextContract $commerceContext;

    protected WebhookSubscriptionRepository $webhookSubscriptionRepository;

    public function __construct(CommerceContextContract $commerceContext, WebhookSubscriptionRepository $webhookSubscriptionRepository)
    {
        $this->commerceContext = $commerceContext;
        $this->webhookSubscriptionRepository = $webhookSubscriptionRepository;
    }

    /**
     * {@inheritDoc}
     */
    protected function getWebhookSecret() : ?string
    {
        if (! $contextId = $this->commerceContext->getId()) {
            return null;
        }

        if (! $subscription = $this->webhookSubscriptionRepository->getSubscriptionByContextId($contextId)) {
            return null;
        }

        return $subscription->secret;
    }
}

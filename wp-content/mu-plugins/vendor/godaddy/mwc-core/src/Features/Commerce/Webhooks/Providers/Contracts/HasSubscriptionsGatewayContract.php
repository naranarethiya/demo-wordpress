<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts;

interface HasSubscriptionsGatewayContract
{
    /**
     * Returns the webhook subscriptions gateway.
     *
     * @return WebhookSubscriptionsGatewayContract
     */
    public function subscriptions() : WebhookSubscriptionsGatewayContract;
}

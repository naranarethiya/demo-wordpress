<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts\WebhookProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts\WebhookSubscriptionsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Gateways\WebhookSubscriptionsGateway;

/**
 * GoDaddy webhook provider.
 */
class WebhookProvider implements WebhookProviderContract
{
    /**
     * {@inheritDoc}
     */
    public function subscriptions() : WebhookSubscriptionsGatewayContract
    {
        return WebhookSubscriptionsGateway::getNewInstance();
    }
}

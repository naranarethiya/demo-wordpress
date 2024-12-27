<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts;

/**
 * Contract for handlers that handle webhook subscriptions.
 */
interface WebhookSubscriptionsGatewayContract extends CanCreateSubscriptionsContract, CanListSubscriptionsContract, CanDeleteSubscriptionsContract
{
}

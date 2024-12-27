<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;

/**
 * Response to creating a new subscription.
 */
interface CreateSubscriptionResponseContract
{
    public function getSubscription() : Subscription;
}

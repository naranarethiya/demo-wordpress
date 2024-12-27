<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts\CreateSubscriptionResponseContract;

/**
 * Response object from a "create subscription" request.
 */
class CreateSubscriptionResponse implements CreateSubscriptionResponseContract
{
    protected Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Gets the subscription object.
     *
     * @return Subscription
     */
    public function getSubscription() : Subscription
    {
        return $this->subscription;
    }
}

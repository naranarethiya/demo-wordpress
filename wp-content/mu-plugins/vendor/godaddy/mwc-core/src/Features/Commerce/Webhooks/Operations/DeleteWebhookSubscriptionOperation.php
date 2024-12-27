<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\Contracts\DeleteWebhookSubscriptionOperationContract;

/**
 * Operation for deleting a webhook subscription.
 */
class DeleteWebhookSubscriptionOperation implements DeleteWebhookSubscriptionOperationContract
{
    use CanSeedTrait;

    protected string $subscriptionId;

    /**
     * {@inheritDoc}
     */
    public function getSubscriptionId() : string
    {
        return $this->subscriptionId;
    }

    /**
     * {@inheritDoc}
     */
    public function setSubscriptionId(string $value) : DeleteWebhookSubscriptionOperationContract
    {
        $this->subscriptionId = $value;

        return $this;
    }
}

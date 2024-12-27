<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;

interface ListSubscriptionsResponseContract
{
    /**
     * Sets the subscriptions.
     *
     * @param Subscription[] $value
     * @return ListSubscriptionsResponseContract
     */
    public function setSubscriptions(array $value) : ListSubscriptionsResponseContract;

    /**
     * Gets the subscriptions.
     *
     * @return Subscription[]
     */
    public function getSubscriptions() : array;
}

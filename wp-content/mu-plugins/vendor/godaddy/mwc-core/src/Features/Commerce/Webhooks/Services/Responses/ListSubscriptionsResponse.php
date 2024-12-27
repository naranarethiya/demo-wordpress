<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts\ListSubscriptionsResponseContract;

/**
 * Response object from a "list subscriptions" request.
 */
class ListSubscriptionsResponse implements ListSubscriptionsResponseContract
{
    use CanGetNewInstanceTrait;

    /** @var Subscription[] */
    protected array $subscriptions = [];

    /** {@inheritDoc} */
    public function setSubscriptions(array $value) : ListSubscriptionsResponseContract
    {
        $this->subscriptions = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getSubscriptions() : array
    {
        return $this->subscriptions;
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\CreateSubscriptionInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;

/**
 * Contract for handlers that can create subscriptions.
 */
interface CanCreateSubscriptionsContract
{
    /**
     * Creates a webhook subscription.
     *
     * @param CreateSubscriptionInput $input
     * @return Subscription
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function create(CreateSubscriptionInput $input) : Subscription;
}

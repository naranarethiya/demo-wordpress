<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\DeleteSubscriptionInput;

/**
 * Contract for handlers that can delete subscriptions.
 */
interface CanDeleteSubscriptionsContract
{
    /**
     * Deletes a webhook subscription.
     *
     * @param DeleteSubscriptionInput $input
     * @return void
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function delete(DeleteSubscriptionInput $input) : void;
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\Handlers\DeleteWebhookSubscriptionJobHandler;

/**
 * Callback for the Action Scheduler job to delete an existing webhook subscription.
 */
class DeleteWebhookSubscriptionJobInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_commerce_delete_webhook_subscription';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setArgumentsCount(1)
            ->setHandler([DeleteWebhookSubscriptionJobHandler::class, 'handle'])
            ->execute();
    }
}

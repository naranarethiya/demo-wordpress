<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\Handlers\UpdateWebhookSubscriptionJobHandler;

/**
 * Callback for the Action Scheduler job to update an existing webhook subscription.
 */
class UpdateWebhookSubscriptionJobInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_commerce_update_webhook_subscription';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setArgumentsCount(1)
            ->setHandler([UpdateWebhookSubscriptionJobHandler::class, 'handle'])
            ->execute();
    }
}

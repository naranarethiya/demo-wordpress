<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\Handlers\CreateWebhookSubscriptionJobHandler;

/**
 * Callback for the Action Scheduler job to create a new subscription.
 */
class CreateWebhookSubscriptionJobInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_commerce_create_webhook_subscription';

    /**
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setArgumentsCount(1)
            ->setHandler([CreateWebhookSubscriptionJobHandler::class, 'handle'])
            ->execute();
    }
}

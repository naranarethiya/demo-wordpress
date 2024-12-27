<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\Handlers\ProcessWebhookJobHandler;

/**
 * Callback for an Action Scheduler job to process webhooks.
 */
class ProcessWebhookJobInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_process_webhook';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setHandler([ProcessWebhookJobHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }
}

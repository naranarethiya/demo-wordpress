<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\Handlers\WebhookSubscriptionHandler;

class WebhookSubscriptionsInterceptor extends AbstractInterceptor
{
    /**
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([WebhookSubscriptionHandler::class, 'handle'])
            ->execute();
    }
}

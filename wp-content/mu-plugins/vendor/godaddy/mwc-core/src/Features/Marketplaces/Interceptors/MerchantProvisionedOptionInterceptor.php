<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\MerchantProvisionedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\MerchantProvisioningHandler;

/**
 * Triggers an event when the flag option to mark the merchant as provisioned is set.
 */
class MerchantProvisionedOptionInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('add_option_'.MerchantProvisioningHandler::$merchant_provisioned_option_key)
            ->setHandler([$this, 'handleOptionSet'])
            ->execute();
    }

    /**
     * Broadcasts an event if the merchant provisioned flag option was just set for the first time.
     *
     * @return void
     */
    public function handleOptionSet() : void
    {
        Events::broadcast(new MerchantProvisionedEvent());
    }
}

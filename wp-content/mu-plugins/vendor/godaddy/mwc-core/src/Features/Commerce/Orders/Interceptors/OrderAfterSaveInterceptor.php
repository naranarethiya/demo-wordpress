<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\Handlers\OrderAfterSaveHandler;

/**
 * Registers a hook handler for woocommerce_after_order_object_save.
 */
class OrderAfterSaveInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('woocommerce_after_order_object_save')
            ->setHandler([OrderAfterSaveHandler::class, 'handle'])
            ->setPriority(PHP_INT_MIN)
            ->setArgumentsCount(1)
            ->execute();
    }
}

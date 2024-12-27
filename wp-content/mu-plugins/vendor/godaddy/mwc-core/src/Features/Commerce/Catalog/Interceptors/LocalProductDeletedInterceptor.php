<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\LocalProductDeletedHandler;

/**
 * Interceptor for executing actions upon local product deletion. {@see wp_delete_post()}.
 */
class LocalProductDeletedInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('delete_post')
            ->setHandler([LocalProductDeletedHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }
}

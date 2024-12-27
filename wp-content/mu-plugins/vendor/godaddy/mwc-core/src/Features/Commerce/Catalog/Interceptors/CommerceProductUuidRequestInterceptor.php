<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\CommerceProductUuidRequestHandler;

/**
 * Intercepts incoming requests which contain a `gd-product-id` query parameter.
 *
 * The GD product ID is expected to be a Commerce UUID. Intercepting these requests enables external services which
 * only know about the Commerce UUID to be redirected to the local permalink.
 */
class CommerceProductUuidRequestInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([CommerceProductUuidRequestHandler::class, 'handle'])
            ->setPriority(10)
            ->execute();
    }
}

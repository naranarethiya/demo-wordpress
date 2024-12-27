<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductTrashedHandler;

/**
 * Interceptor to execute actions when a product is moved to the trash.
 */
class ProductTrashedInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('trash_product') /* @see wp_transition_post_status() */
            ->setHandler([ProductTrashedHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }
}

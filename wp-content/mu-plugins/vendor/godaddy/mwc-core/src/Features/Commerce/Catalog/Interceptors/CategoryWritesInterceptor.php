<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\CategoryWritesHandler;

/**
 * Interceptor to register hooks for facilitating category writes.
 */
class CategoryWritesInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        /*
         * Fires after a product category is created or updated locally.
         *
         * @see wp_insert_term()
         * @see wp_update_term()
         */
        Register::action()
            ->setGroup('saved_product_cat')
            ->setHandler([CategoryWritesHandler::class, 'handle'])
            ->setArgumentsCount(4)
            ->execute();
    }
}

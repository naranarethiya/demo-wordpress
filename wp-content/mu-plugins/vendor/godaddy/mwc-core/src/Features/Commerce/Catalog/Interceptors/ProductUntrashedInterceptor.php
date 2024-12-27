<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductUntrashedHandler;

class ProductUntrashedInterceptor extends AbstractInterceptor
{
    /**
     * Hook into products which have been untrashed.
     *
     * Unfortunately, neither WordPress Core nor WooCommerce have a post type specific hook for this,
     * so we have to use the generic `untrashed_post` hook. Therefore, the handler must take further steps to determine
     * if the hook is firing on a valid product.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('untrashed_post')
            ->setHandler([ProductUntrashedHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }
}

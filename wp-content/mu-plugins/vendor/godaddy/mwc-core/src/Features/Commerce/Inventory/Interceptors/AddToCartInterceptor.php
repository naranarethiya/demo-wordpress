<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers\AddToCartCacheHandler;

class AddToCartInterceptor extends AbstractInterceptor
{
    /**
     * Registers the hooks.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('woocommerce_add_to_cart_product_id')
            ->setHandler([AddToCartCacheHandler::class, 'handle'])
            ->execute();
    }
}

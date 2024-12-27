<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\CrossSellProductsHandler;

/**
 * Intercepts the retrieval of cross-sell products to improve performance (N+1 issues).
 */
class CrossSellProductsInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @see \WC_Cart::get_cross_sells()
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('woocommerce_cart_crosssell_ids')
            ->setHandler([CrossSellProductsHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }
}

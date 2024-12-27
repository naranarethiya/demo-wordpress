<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\RelatedProductsHandler;

/**
 * Interceptor execute actions when local related products are fetched. {@see wc_get_related_products()}.
 */
class RelatedProductsInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('woocommerce_related_products')
            ->setHandler([RelatedProductsHandler::class, 'handle'])
            ->setArgumentsCount(3)
            ->execute();
    }
}

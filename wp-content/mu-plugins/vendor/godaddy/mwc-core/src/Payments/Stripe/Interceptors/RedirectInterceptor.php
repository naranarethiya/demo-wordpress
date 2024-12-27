<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Interceptors;

use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;

/**
 * Intercepts the redirect after payment is processed.
 */
class RedirectInterceptor extends AbstractInterceptor
{
    /**
     * Determines whether the component should be loaded.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        // TODO: Implement shouldLoad() method in mwc-6446 {ssmith1 2022-06-14}
        return false;
    }

    /**
     * Adds the hook along with callback.
     */
    public function addHooks()
    {
        // TODO: Implement addHooks() method in story mwc-6446 {ssmith1 2022-06-14}
    }
}

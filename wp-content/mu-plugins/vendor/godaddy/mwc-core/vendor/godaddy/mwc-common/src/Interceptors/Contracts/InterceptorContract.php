<?php

namespace GoDaddy\WordPress\MWC\Common\Interceptors\Contracts;

/**
 * The contract for interceptor classes.
 *
 * Classes implementing this interface are able to hook into actions and filters to intercept WordPress and WooCommerce
 * operations.
 */
interface InterceptorContract
{
    /**
     * Should implement action and filter hooks.
     */
    public function addHooks();
}

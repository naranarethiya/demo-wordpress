<?php

namespace GoDaddy\WordPress\MWC\Common\Interceptors;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Interceptors\Contracts\InterceptorContract;

/**
 * The abstraction of an interceptor.
 *
 * It allows interceptors to behave as conditional components.
 */
abstract class AbstractInterceptor implements InterceptorContract, ConditionalComponentContract
{
    /**
     * {@inheritDoc}
     */
    public function load()
    {
        $this->addHooks();
    }

    /**
     * Determines whether the component should be loaded or not.
     *
     * Every interceptor is loaded by default, so implementations must override this method if it should load conditionally.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return true;
    }
}

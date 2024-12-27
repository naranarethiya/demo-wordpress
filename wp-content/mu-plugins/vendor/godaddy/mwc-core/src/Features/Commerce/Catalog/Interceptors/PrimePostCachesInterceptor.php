<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\PrimePostCachesHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CustomWordPressCoreHook;

/**
 * Interceptor for priming product post caches.
 */
class PrimePostCachesInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        /* @see _prime_post_caches() */
        Register::filter()
            ->setGroup(CustomWordPressCoreHook::PrimePostCaches_Posts)
            ->setHandler([PrimePostCachesHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }
}

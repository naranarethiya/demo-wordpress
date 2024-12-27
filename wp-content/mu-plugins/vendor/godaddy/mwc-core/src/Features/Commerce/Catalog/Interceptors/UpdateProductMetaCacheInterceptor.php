<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\UpdateProductMetaCacheHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanLoadWhenReadsEnabledTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CustomWordPressCoreHook;

/**
 * Interceptor for updating the product meta cache. {@see update_meta_cache()}.
 */
class UpdateProductMetaCacheInterceptor extends AbstractInterceptor
{
    use CanLoadWhenReadsEnabledTrait;

    /**
     * Adds the hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup(CustomWordPressCoreHook::UpdateMetaCache)
            ->setHandler([UpdateProductMetaCacheHandler::class, 'handle'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(3)
            ->execute();
    }
}

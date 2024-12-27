<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\CreateOrUpdateRemoteVariantsJobHandler;

/**
 * Handles a scheduled job to create or update remote variants for a product.
 */
class CreateOrUpdateRemoteVariantsInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_commerce_catalog_create_update_remote_variants';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        RegisterAction::action()
            ->setGroup(static::JOB_NAME)
            ->setArgumentsCount(1)
            ->setHandler([CreateOrUpdateRemoteVariantsJobHandler::class, 'handle'])
            ->execute();
    }
}

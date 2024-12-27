<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\SaveLocalProductAfterRemoteUpdateHandler;

/**
 * Handles a scheduled job to respond when a product is updated in the remote platform.
 */
class SaveLocalProductAfterRemoteUpdateInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_commerce_catalog_handle_remote_product_update';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setArgumentsCount(1)
            ->setHandler([SaveLocalProductAfterRemoteUpdateHandler::class, 'handle'])
            ->execute();
    }
}

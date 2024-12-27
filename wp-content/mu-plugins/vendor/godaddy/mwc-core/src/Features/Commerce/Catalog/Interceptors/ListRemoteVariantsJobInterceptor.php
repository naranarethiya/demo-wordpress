<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ListRemoteVariantsJobHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Polling\RemoteProductsPollingProcessor;

/**
 * Handles scheduled job to list product variants. See {@see RemoteProductsPollingProcessor}.
 */
class ListRemoteVariantsJobInterceptor extends AbstractInterceptor
{
    /** @var string name of the recurring job action */
    public const JOB_NAME = 'mwc_gd_commerce_catalog_list_remote_variants';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        RegisterAction::action()
            ->setGroup(static::JOB_NAME)
            ->setArgumentsCount(2)
            ->setHandler([ListRemoteVariantsJobHandler::class, 'handle'])
            ->execute();
    }
}

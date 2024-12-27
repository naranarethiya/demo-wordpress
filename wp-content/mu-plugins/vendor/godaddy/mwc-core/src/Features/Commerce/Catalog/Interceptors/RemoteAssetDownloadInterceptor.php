<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\RemoteAssetDownloadHandler;

/**
 * Registers the callback for an async job to download remote assets to the local site via their attachment IDs.
 *
 * We do this in order to obtain data about the remote asset that is not yet included in the API.
 */
class RemoteAssetDownloadInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_commerce_catalog_download_remote_assets';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setHandler([RemoteAssetDownloadHandler::class, 'handle'])
            ->setArgumentsCount(1)
            ->execute();
    }
}

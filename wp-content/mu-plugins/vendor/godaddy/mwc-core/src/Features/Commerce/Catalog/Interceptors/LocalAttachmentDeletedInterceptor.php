<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\LocalAttachmentDeletedHandler;

/**
 * Interceptor for executing actions upon local attachment deletion. {@see wp_delete_attachment()}.
 */
class LocalAttachmentDeletedInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('delete_post')
            ->setHandler([LocalAttachmentDeletedHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }
}

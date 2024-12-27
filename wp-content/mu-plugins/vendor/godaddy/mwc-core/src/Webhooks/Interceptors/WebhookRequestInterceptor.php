<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\Handlers\WebhookRequestHandler;

/**
 * Handles parsing webhook requests.
 */
class WebhookRequestInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('query_vars')
            ->setHandler([$this, 'addQueryVars'])
            ->setPriority(0)
            ->execute();

        Register::action()
            ->setGroup('parse_request')
            ->setHandler([WebhookRequestHandler::class, 'handle'])
            ->setPriority(0)
            ->execute();
    }

    /**
     * Registers the `mwc-webhooks` query var with WordPress.
     *
     * @internal
     *
     * @param string[]|mixed $queryVars
     * @return string[]|mixed
     */
    public function addQueryVars($queryVars)
    {
        if (is_array($queryVars)) {
            $queryVars[] = 'mwc-webhooks';
        }

        return $queryVars;
    }
}

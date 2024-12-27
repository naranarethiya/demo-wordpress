<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\WordPress\Overrides;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Filters WordPress > Settings > Reading settings behavior.
 */
class ReadingSettingsInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::filter()
            ->setGroup('pre_option_blog_public')
            ->setHandler([$this, 'discourageSearchEnginesForTemporaryDomains'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Filters `blog_public` to return falsey value and trick WordPress to discourage search engines if on a temporary domain.
     *
     * @internal
     *
     * @param false|mixed $value
     * @return false|mixed
     */
    public function discourageSearchEnginesForTemporaryDomains($value)
    {
        try {
            if (PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isTemporaryDomain()) {
                return '0';
            }
        } catch (Exception $exception) {
            // since we are in a hook callback context we should catch the exception instead of throwing
        }

        return $value;
    }
}

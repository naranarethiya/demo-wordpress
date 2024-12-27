<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Overrides items in WordPress > Settings > General.
 */
class GeneralSettingsInterceptor extends AbstractInterceptor
{
    /**
     * Disables editing of the WordPress Home URL and Site URL in Settings > General.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'disableEditingHomeUrl'])
            ->setPriority(PHP_INT_MIN)
            ->execute();

        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'disableEditingSiteUrl'])
            ->setPriority(PHP_INT_MIN)
            ->execute();
    }

    /**
     * Disables editing of the WordPress Address (URL).
     *
     * The setting in wp-admin is automatically disabled by WordPress if the constant is defined.
     *
     * @internal
     *
     * @return void
     */
    public function disableEditingHomeUrl() : void
    {
        $url = get_option('home');

        if (ValidationHelper::isUrl($url) && ! defined('WP_HOME')) {
            define('WP_HOME', $url);
        }
    }

    /**
     * Disables editing of the Site Address (URL).
     *
     * The setting in wp-admin is automatically disabled by WordPress if the constant is defined.
     *
     * @internal
     *
     * @return void
     */
    public function disableEditingSiteUrl() : void
    {
        $url = get_option('siteurl');

        if (ValidationHelper::isUrl($url) && ! defined('WP_SITEURL')) {
            define('WP_SITEURL', $url);
        }
    }
}

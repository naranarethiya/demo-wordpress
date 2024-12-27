<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Repository handler for WordPress theme properties and functions.
 */
class ThemeRepository
{
    /**
     * Gets name of the current active theme.
     *
     * @return string
     */
    public static function getActiveThemeName() : string
    {
        return function_exists('get_template') ? TypeHelper::string(get_template(), '') : '';
    }

    /**
     * Gets theme modification value for the active theme.
     *
     * @param non-empty-string $name
     * @return mixed|null
     */
    public static function getThemeMod(string $name)
    {
        return function_exists('get_theme_mod') ? get_theme_mod($name) : null;
    }

    /**
     * Gets currently set custom logo value.
     *
     * @return string
     */
    public static function getCustomLogo() : string
    {
        return (string) StringHelper::ensureScalar(static::getThemeMod('custom_logo'));
    }
}

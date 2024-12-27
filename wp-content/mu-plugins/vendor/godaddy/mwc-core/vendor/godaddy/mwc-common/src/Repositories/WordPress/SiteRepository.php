<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

/**
 * Repository handler for WordPress site properties and functions.
 */
class SiteRepository
{
    /**
     * Gets the site domain.
     *
     * @return string
     */
    public static function getDomain() : string
    {
        $domain = wp_parse_url(static::getHomeUrl(), PHP_URL_HOST) ?: '';

        return is_string($domain) ? $domain : '';
    }

    /**
     * Determines whether the current request is using the HTTPS protocol.
     */
    public static function isSsl() : bool
    {
        return function_exists('is_ssl') && is_ssl();
    }

    /**
     * Gets the site URL.
     *
     * In WordPress, this is the value of the "WordPress Address (URL)" input field in the General Settings page.
     * The site URL points to the WordPress installation.
     * In a normal installation this matches the root, however WordPress could be installed in a subdirectory.
     *
     * @return string
     */
    public static function getSiteUrl() : string
    {
        $siteUrl = function_exists('site_url') ? site_url() : '';

        return is_string($siteUrl) ? $siteUrl : '';
    }

    /**
     * Gets the home URL.
     *
     * In WordPress, this is the value of the "Site Address (URL)" input field in the General Settings page.
     * The home URL points to the home page.
     *
     * @return string
     */
    public static function getHomeUrl() : string
    {
        $homeUrl = function_exists('home_url') ? home_url() : '';

        return is_string($homeUrl) ? $homeUrl : '';
    }

    /**
     * Gets the admin URL to the WordPress dashboard.
     *
     * @param string $path optional relative path
     * @return string
     */
    public static function getAdminUrl(string $path = '') : string
    {
        return admin_url($path);
    }

    /**
     * Gets the site title.
     *
     * @return string
     */
    public static function getTitle() : string
    {
        $title = function_exists('get_bloginfo') ? get_bloginfo('name') : '';

        return is_string($title) ? $title : '';
    }

    /**
     * Gets the site language.
     *
     * @return string
     */
    public static function getLanguage() : string
    {
        $language = function_exists('get_bloginfo') ? get_bloginfo('language') : '';

        return is_string($language) ? $language : '';
    }

    /**
     * Converts the path of a plugin or mu-plugin into a URL.
     *
     * Note that this only works inside the context of a MU Plugin or a traditional plugin. Any path that is not inside
     * either of these directories will cause unexpected behavior.
     *
     * @param $path string The absolute path of the directory to get as a URL.
     *
     * @return string
     */
    public static function getPluginUrl(string $path = '') : string
    {
        $url = function_exists('plugin_dir_url') ? plugin_dir_url($path) : '';

        return is_string($url) ? $url : '';
    }

    /**
     * Gets the WordPress REST API URL.
     *
     * @see \get_rest_url()
     *
     * @param int|null $blogId optional, defaults to current blog
     * @param string $path optional, defaults to root
     * @param string $sanitizationScheme defaults to 'rest'
     * @return string
     */
    public static function getRestUrl(?int $blogId = null, string $path = '/', string $sanitizationScheme = 'rest') : string
    {
        return (string) get_rest_url($blogId, $path, $sanitizationScheme);
    }
}

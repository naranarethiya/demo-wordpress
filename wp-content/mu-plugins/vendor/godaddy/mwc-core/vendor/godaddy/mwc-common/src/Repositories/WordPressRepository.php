<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use ArrayAccess;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\Context\Screen;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\WordPressScreenAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\UserLogInException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Http\Url;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlException;
use GoDaddy\WordPress\MWC\Common\Models\User;
use WP;
use WP_Comment;
use WP_Error;
use WP_Screen;

/**
 * WordPress repository handler.
 */
class WordPressRepository
{
    /**
     * Gets the main WordPress environment setup class.
     *
     * @return WP
     * @throws Exception
     */
    public static function getInstance() : WP
    {
        global $wp;

        if (! $wp || ! is_a($wp, 'WP')) {
            throw new Exception('WordPress environment not initialized.');
        }

        return $wp;
    }

    /**
     * Gets the plugin's assets URL.
     *
     * @param string $path optional path
     * @return string URL
     */
    public static function getAssetsUrl(string $path = '') : string
    {
        $config = TypeHelper::string(Configuration::get('mwc.url'), '');

        if (empty($config)) {
            return '';
        }

        $url = StringHelper::trailingSlash($config);

        return "{$url}assets/{$path}";
    }

    /**
     * Gets the current blog ID.
     *
     * @see \get_current_blog_id()
     *
     * @return int
     */
    public static function getCurrentBlogId() : int
    {
        return get_current_blog_id();
    }

    /**
     * Gets the default email address used as From address by {@see wp_mail()}.
     */
    public static function getDefaultEmailAddress() : string
    {
        $emailAddress = 'wordpress@';

        if (! $url = static::getNetworkHomeUrl()) {
            return $emailAddress;
        }

        $host = $url->getHost();

        if (StringHelper::startsWith($host, 'www.')) {
            return $emailAddress.StringHelper::after($host, 'www.');
        }

        return $emailAddress.$host;
    }

    /**
     * Gets the WordPress Filesystem instance.
     *
     * @return array|ArrayAccess|mixed
     * @throws Exception
     */
    public static function getFilesystem()
    {
        $wpFilesystem = ArrayHelper::get($GLOBALS, 'wp_filesystem');

        if (! $wpFilesystem || ! is_object($wpFilesystem)) {
            throw new Exception('Unable to connect to the WordPress filesystem -- wp_filesystem global not found');
        }

        if (is_a($wpFilesystem, 'WP_Filesystem_Base') && is_wp_error($wpFilesystem->errors) && $wpFilesystem->errors->has_errors()) {
            throw new Exception(sprintf('Unable to connect to the WordPress filesystem with error: %s', $wpFilesystem->errors->get_error_message()));
        }

        return $wpFilesystem;
    }

    /**
     * Gets the home URL for the current network.
     *
     * If multisite is disabled, the home URL of the current network is the URL where the front of site is accessible.
     *
     * @return Url|null
     */
    public static function getNetworkHomeUrl() : ?Url
    {
        if (! function_exists('network_home_url')) {
            return null;
        }

        try {
            return Url::fromString(network_home_url());
        } catch (InvalidUrlException $exception) {
            return null;
        }
    }

    /**
     * Gets the current WordPress Version.
     *
     * @return string|null
     */
    public static function getVersion() : ?string
    {
        $version = TypeHelper::string(Configuration::get('wordpress.version'), '');

        return ! empty($version) ? $version : null;
    }

    /**
     * Determines that a WordPress instance can be found.
     *
     * @return bool
     */
    public static function hasWordPressInstance() : bool
    {
        return (bool) static::getWordPressAbsolutePath();
    }

    /**
     * Determines if the current instance is in CLI mode.
     *
     * @return bool
     */
    public static function isCliMode() : bool
    {
        return 'cli' === Configuration::get('mwc.mode');
    }

    /**
     * Determines whether WordPress is in debug mode.
     *
     * @return bool
     */
    public static function isDebugMode() : bool
    {
        return (bool) Configuration::get('wordpress.debug');
    }

    /**
     * Determines if the current request is for a WC REST API endpoint.
     *
     * @see WooCommerce::is_rest_api_request()
     *
     * @return bool
     */
    public static function isApiRequest() : bool
    {
        if (! $_SERVER['REQUEST_URI'] || ! function_exists('rest_get_url_prefix')) {
            return false;
        }

        $is_rest_api_request = StringHelper::contains($_SERVER['REQUEST_URI'], StringHelper::trailingSlash(rest_get_url_prefix()));

        /* applies WooCommerce core filter */
        return (bool) apply_filters('woocommerce_is_rest_api_request', $is_rest_api_request);
    }

    /**
     * Determines whether the current WordPress thread is a request for a WordPress admin page.
     *
     * @return bool
     */
    public static function isAdmin() : bool
    {
        return static::hasWordPressInstance() && is_admin();
    }

    /**
     * Determines whether the current WordPress thread is executing an AJAX callback.
     *
     * @return bool
     */
    public static function isAjax() : bool
    {
        return function_exists('wp_doing_ajax') && is_callable('wp_doing_ajax')
            ? wp_doing_ajax()
            : defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Determines if a given value is to be considered a WordPress error.
     *
     * We don't necessarily rely on instanceof as the WordPress function wrapped here will also filter the result.
     *
     * @param mixed $value
     * @return bool
     * @phpstan-assert-if-true WP_Error $value
     */
    public static function isError($value) : bool
    {
        return (bool) is_wp_error($value);
    }

    /**
     * Gets the WordPress absolute path.
     *
     * @return string
     */
    public static function getWordPressAbsolutePath() : string
    {
        return TypeHelper::string(Configuration::get('wordpress.absolute_path'), '');
    }

    /**
     * Requires the absolute path to the WordPress directory.
     *
     * @return void
     * @throws Exception
     */
    public static function requireWordPressInstance() : void
    {
        if (! static::hasWordPressInstance()) {
            // @TODO setting to throw an exception for now, may have to be revisited later (or possibly with a less generic exception) {FN 2020-12-18}
            throw new Exception('Unable to find the required WordPress instance');
        }
    }

    /**
     * Initializes and connect the WordPress Filesystem instance.
     *
     * Implementation adapted from {@see delete_plugins()}.
     *
     * @return void
     * @throws Exception
     */
    public static function requireWordPressFilesystem() : void
    {
        $base = static::getWordPressAbsolutePath();

        require_once "{$base}wp-admin/includes/file.php";
        require_once "{$base}wp-admin/includes/plugin-install.php";
        require_once "{$base}wp-admin/includes/class-wp-upgrader.php";
        require_once "{$base}wp-admin/includes/plugin.php";

        // we are using an empty string as the value for the $form_post parameter because it is not relevant for our test.
        // If the function needs to show the form then the WordPress Filesystem is not currently configured for our needs.
        // We need to be able to access the filesystem without asking the user for credentials.
        ob_start();
        /** @var array<mixed>|false $credentials */
        $credentials = request_filesystem_credentials('');
        ob_end_clean();

        if (false === $credentials || ! WP_Filesystem($credentials)) {
            static::getFilesystem();

            throw new Exception('Unable to connect to the WordPress filesystem');
        }
    }

    /**
     * Requires the WordPress Upgrade API.
     *
     * @return void
     */
    public static function requireWordPressUpgradeAPI() : void
    {
        $base = static::getWordPressAbsolutePath();

        require_once "{$base}wp-admin/includes/upgrade.php";
    }

    /**
     * Requires the WordPress User Administration API.
     *
     * @return void
     */
    public static function requireWordPressUserAdministrationAPI() : void
    {
        $base = static::getWordPressAbsolutePath();

        require_once "{$base}wp-admin/includes/user.php";
    }

    /**
     * Gets a Screen object using the data from the current WordPress screen object.
     *
     * @NOTE to reliably use this method, the screen should be grabbed past the `admin_init` hook or {@see \get_current_screen()} may not be available {unfulvio 2022-02-09}
     *
     * @return Screen|null
     */
    public static function getCurrentScreen()
    {
        $currentWPScreen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (! $currentWPScreen instanceof WP_Screen) {
            return null;
        }

        return new Screen((new WordPressScreenAdapter($currentWPScreen))->convertFromSource());
    }

    /**
     * Determines if the current screen is a given WordPress admin screen for a given screen ID.
     *
     * @param string|string[] $screenId individual screen ID or list of IDs
     * @return bool
     * @throws Exception to use this method, the check should be executed past the `admin_init` hook
     */
    public static function isCurrentScreen($screenId) : bool
    {
        if (! function_exists('get_current_screen')) {
            throw new Exception('Unable to determine the current screen.');
        }

        $currentScreen = get_current_screen();

        return $currentScreen && ArrayHelper::contains(ArrayHelper::wrap($screenId), $currentScreen->id);
    }

    /**
     * Gets WordPress instance current locale setting.
     *
     * @return string
     */
    public static function getLocale() : string
    {
        return Configuration::get('wordpress.locale', '');
    }

    /**
     * Gets a WP_Comment object given a comment ID.
     *
     * @param int $commentId
     * @return WP_Comment|null
     */
    public static function getComment(int $commentId) : ?WP_Comment
    {
        $comment = get_comment($commentId);

        return $comment instanceof WP_Comment ? $comment : null;
    }

    /**
     * Returns all active plugins.
     *
     * @return array
     */
    public static function getActivePlugins() : array
    {
        return get_option('active_plugins', []);
    }

    /**
     * Gets the basename (e.g. "plugin/plugin.php") from the slug (e.g. "plugin").
     *
     * This will only return a value if the supplied slug is an _installed_ plugin (not necessarily activated).
     *
     * @param string $slug slug (directory name) of the plugin to check
     * @return string|null basename if found, otherwise null
     */
    public static function getPluginBasenameFromSlug(string $slug) : ?string
    {
        if (! function_exists('get_plugins')) {
            return null;
        }

        $installedPlugins = array_keys(get_plugins());
        foreach ($installedPlugins as $installedPluginBasename) {
            if (StringHelper::startsWith($installedPluginBasename, "{$slug}/")) {
                return $installedPluginBasename;
            }
        }

        return null;
    }

    /**
     * Gets the path to the must-use plugins directory.
     *
     * @return string
     */
    public static function getMustUsePluginsDirectoryPath() : string
    {
        return defined('WPMU_PLUGIN_DIR') ? StringHelper::trailingSlash(WPMU_PLUGIN_DIR) : '';
    }

    /**
     * Gets the URL to the must-use plugins directory.
     *
     * @return string
     */
    public static function getMustUsePluginsDirectoryUrl() : string
    {
        return defined('WPMU_PLUGIN_URL') ? StringHelper::trailingSlash(WPMU_PLUGIN_URL) : '';
    }

    /**
     * Retrieves the URL for an attachment.
     *
     * @param int $attachmentId
     *
     * @return string|false
     */
    public static function getAttachmentUrl(int $attachmentId)
    {
        return wp_get_attachment_url($attachmentId);
    }

    /**
     * Logs in a given user.
     *
     * @param User $user
     * @return void
     * @throws UserLogInException
     */
    public static function logInUser(User $user) : void
    {
        $userId = (int) $user->getId();

        if ($userId <= 0) {
            throw new UserLogInException(__('User does not have a valid ID.', 'mwc-common'));
        }

        wp_set_current_user($userId);
        wp_set_auth_cookie($userId);

        /*
         * @see wp_signon() for where this is triggered in WordPress core.
         */
        do_action('wp_login', $user->getHandle(), get_userdata($userId));

        if (! $user->isLoggedIn()) {
            throw new UserLogInException(__('User could not be logged in.', 'mwc-common'));
        }
    }

    /**
     * Redirects the user to the given URL.
     *
     * @deprecated
     * @see Redirect instead
     *
     * @param string $url URL to redirect to
     * @param bool $allowUnsafe default false, set to true to use {@see \wp_redirect()} instead of {@see \wp_safe_redirect()}
     * @param int $httpStatusCode optional HTTP status code to use for the redirection (default 302)
     * @param string $redirectedBy optional application prompting the redirect (default 'WordPress')
     * @return bool
     */
    public static function redirectTo(string $url, bool $allowUnsafe = false, int $httpStatusCode = 302, string $redirectedBy = 'WordPress') : bool
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.30', '\GoDaddy\WordPress\MWC\Common\Http\Redirect::to');

        try {
            Redirect::to($url)
                ->setSafe(! $allowUnsafe)
                ->setStatusCode($httpStatusCode)
                ->setRedirectBy($redirectedBy)
                ->execute();

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /*
     * Determines whether the request is Network admin request or not.
     *
     * @return bool
     */
    public static function isNetworkAdminRequest() : bool
    {
        return function_exists('is_network_admin') && is_network_admin();
    }

    /**
     * Determines if the multisite mode is enabled or not.
     *
     * @return bool
     */
    public static function isMultisite() : bool
    {
        return function_exists('is_multisite') && is_multisite();
    }
}

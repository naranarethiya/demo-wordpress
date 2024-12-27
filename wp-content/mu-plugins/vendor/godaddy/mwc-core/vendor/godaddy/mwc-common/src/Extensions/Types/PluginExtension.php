<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\PluginAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionActivationFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDeactivationFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionInstallFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionUninstallFailedException;
use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use WP_Error;

/**
 * The plugin extension class.
 */
class PluginExtension extends AbstractExtension
{
    /** @var string asset type */
    const TYPE = 'plugin';

    /** @var string|null The plugin's basename, e.g. some-plugin/some-plugin.php */
    protected $basename;

    /** @var string|null the extension install path */
    protected $installPath;

    /** @var array<string, string> key-value list of available icon URLs */
    protected $imageUrls = [];

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        $this->type = self::TYPE;
        $this->installPath = Configuration::get('wordpress.plugins_directory');
    }

    /**
     * Gets the plugin basename.
     *
     * e.g. woocommerce-plugin/woocommerce-plugin.php
     *
     * @return string|null
     */
    public function getBasename() : ?string
    {
        return $this->basename;
    }

    /**
     * Gets the plugin image URLs.
     *
     * @return array<string, string>
     */
    public function getImageUrls() : array
    {
        return $this->imageUrls;
    }

    /**
     * Gets the plugin install path.
     *
     * @return string|null
     */
    public function getInstallPath() : ?string
    {
        return $this->installPath;
    }

    /**
     * Gets the currently installed plugin version or returns null.
     *
     * @return string|null
     */
    public function getInstalledVersion() : ?string
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            // assume the plugin is not installed to avoid changing the contract of the method to start throwing an exception
            return null;
        }

        if (! $this->isInstalled()) {
            return null;
        }

        return ArrayHelper::get(get_plugin_data(StringHelper::trailingSlash($this->getInstallPath()).$this->getBasename()), 'Version');
    }

    /**
     * Gets the plugin name.
     *
     * @return string|null
     */
    public function getName() : ?string
    {
        if ($this->name && StringHelper::startsWith($this->name, 'WooCommerce')) {
            return trim(StringHelper::after($this->name, 'WooCommerce'));
        }

        return $this->name;
    }

    /**
     * Sets the plugin basename.
     *
     * e.g. woocommerce-plugin/woocommerce-plugin.php
     *
     * @param string $value basename value to set
     * @return $this
     */
    public function setBasename(string $value) : PluginExtension
    {
        $this->basename = $value;

        return $this;
    }

    /**
     * Automatically updates the plugin basename by looking it up via {@see get_plugins()}.
     *
     * This will only set a basename value if this extension is currently installed (but not necessarily activated).
     * It's recommended to call this immediately after a plugin is downloaded {@see static::install()} so that we have
     * accurate basename information.
     *
     * @return void
     */
    public function maybeAutoUpdateBasename() : void
    {
        if (! $slug = $this->getSlug()) {
            return;
        }

        if ($basename = WordPressRepository::getPluginBasenameFromSlug($slug)) {
            $this->setBasename($basename);
        }
    }

    /**
     * Sets the plugin image URLs.
     *
     * @param string[] $urls URLs to set
     * @return $this
     */
    public function setImageUrls(array $urls) : PluginExtension
    {
        $this->imageUrls = $urls;

        return $this;
    }

    /**
     * Activates the plugin.
     *
     * @return void
     * @throws ExtensionActivationFailedException
     */
    public function activate() : void
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionActivationFailedException($exception->getMessage(), $exception);
        }

        if (! $this->isInstalled()) {
            throw new ExtensionActivationFailedException(sprintf('Could not activate %s: the plugin is not installed.', $this->getName() ?? 'a plugin'));
        }

        $activated = activate_plugin($this->getBasename());

        if ($activated instanceof WP_Error) {
            throw new ExtensionActivationFailedException($activated->get_error_message());
        }
    }

    /**
     * Determines whether the plugin is active.
     *
     * @return bool
     */
    public function isActive() : bool
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            // assume the plugin is not active to avoid changing the contract of the method to start throwing an exception
            return false;
        }

        return (bool) is_plugin_active($this->getBasename());
    }

    /**
     * Determines if the plugin is blocked.
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public function isBlocked() : bool
    {
        if (empty($this->getBasename())) {
            return false;
        }

        $pluginDirectory = StringHelper::before($this->getBasename(), '/');
        $blockedPlugins = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getBlockedPlugins();

        return ArrayHelper::contains($blockedPlugins, $pluginDirectory);
    }

    /**
     * Deactivates the plugin.
     *
     * @return void
     * @throws ExtensionDeactivationFailedException
     */
    public function deactivate() : void
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionDeactivationFailedException($exception->getMessage(), $exception);
        }

        deactivate_plugins($this->getBasename());

        if ($this->isActive()) {
            throw new ExtensionDeactivationFailedException(sprintf('%s was not deactivated successfully.', $this->getName() ?? 'A plugin'));
        }
    }

    /**
     * Installs the plugin.
     *
     * @return void
     * @throws ExtensionInstallFailedException
     */
    public function install() : void
    {
        try {
            $downloadable = $this->download();
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionInstallFailedException($exception->getMessage(), $exception);
        }

        $result = unzip_file($downloadable, $this->installPath);

        unlink($downloadable);

        if ($result instanceof WP_Error) {
            throw new ExtensionInstallFailedException($result->get_error_message());
        }

        // make sure to clear out plugins list cache after the plugin successfully installed
        wp_clean_plugins_cache();

        $this->maybeAutoUpdateBasename();

        if (! $this->isInstalled()) {
            throw new ExtensionInstallFailedException(sprintf('%s was not installed successfully.', $this->getName() ?? 'A plugin'));
        }
    }

    /**
     * Determines if the plugin is installed.
     *
     * @return bool
     */
    public function isInstalled() : bool
    {
        return $this->installPath
            && $this->getBasename()
            && is_readable(StringHelper::trailingSlash($this->installPath).$this->getBasename());
    }

    /**
     * Uninstall the Plugin.
     *
     * Implementation adapted from {@see wp_ajax_delete_plugin()}.
     *
     * @return void
     * @throws ExtensionDeactivationFailedException|ExtensionUninstallFailedException
     */
    public function uninstall() : void
    {
        if (! $this->isInstalled()) {
            return;
        }

        if ($this->isActive()) {
            $this->deactivate();
        }

        /* check filesystem credentials first because {@see delete_plugins()} will terminate the PHP process if credentials cannot be retrieved or are invalid */
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionUninstallFailedException($exception->getMessage(), $exception);
        }

        $result = delete_plugins([$this->getBasename()]);

        if ($result instanceof WP_Error) {
            throw new ExtensionUninstallFailedException($result->get_error_message());
        }

        if ($this->isInstalled()) {
            throw new ExtensionUninstallFailedException(sprintf('%s was not uninstalled successfully.', $this->getName() ?? 'A plugin'));
        }
    }

    /**
     * Gets a plugin extension instance by the absolute path to the plugin file (e.g. `/srv/www/godaddy/public_html/wp-content/plugins/woocommerce/woocommerce.php`).
     *
     * @NOTE this will return dummy data from {@see plugin_get_data()} if the plugin is not installed, so you might want to check {@see PluginExtension::isInstalled()} if that's important {unfulvio 2022-02-07}.
     *
     * @param string $identifier absolute path to the main plugin file.
     * @return PluginExtension|null
     */
    public static function get($identifier) : ?PluginExtension
    {
        if (! is_string($identifier) || ! function_exists('get_plugin_data') || ! function_exists('plugin_basename')) {
            return null;
        }

        return PluginAdapter::getNewInstance(plugin_basename($identifier), get_plugin_data($identifier, false, false))->convertFromSource();
    }

    /**
     * Gets a plugin extension instance by its directory name (e.g. `woocommerce`).
     *
     * @NOTE this will return dummy data from {@see plugin_get_data()} if the plugin is not installed, so you might want to check {@see PluginExtension::isInstalled()} if that's important {agibson 2022-10-12}.
     *
     * @param string $directoryName
     * @return PluginExtension|null
     */
    public static function getByDirectoryName(string $directoryName) : ?PluginExtension
    {
        if (! function_exists('get_plugins')) {
            return null;
        }

        $plugins = get_plugins("/{$directoryName}");
        if (empty($plugins) || ! ArrayHelper::accessible($plugins)) {
            return null;
        }

        // assume the requested plugin is the first in the list.
        $pluginFileName = array_keys($plugins)[0] ?? null;
        if (empty($pluginFileName)) {
            return null;
        }

        return static::get(Configuration::get('wordpress.plugins_directory')."/{$directoryName}/{$pluginFileName}");
    }
}

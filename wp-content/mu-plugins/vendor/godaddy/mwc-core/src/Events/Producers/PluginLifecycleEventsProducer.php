<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\ErrorEvent;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Events\PluginLifecycleEvent;
use WP_Error;
use WP_Upgrader;

/**
 * Plugin extension lifecycle events producer.
 *
 * This handler will intercept plugin lifecycle actions in WordPress to broadcast events.
 *
 * @see PluginExtension
 */
class PluginLifecycleEventsProducer extends AbstractInterceptor implements ProducerContract
{
    /**
     * Loads the producer.
     *
     * @return void
     * @throws Exception
     */
    public function load() : void
    {
        $this->addHooks();
    }

    /**
     * Sets up the producer.
     *
     * @return void
     */
    public function setup() : void
    {
        // no-op: deprecated method!
    }

    /**
     * Hooks into WordPress to intercept plugin lifecycle events.
     *
     * @internal
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        /* @see \activate_plugin() */
        Register::action()
            ->setGroup('activated_plugin')
            ->setHandler([$this, 'onActivatedPlugin'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(1)
            ->execute();

        /* @see \deactivate_plugins() */
        Register::action()
            ->setGroup('deactivated_plugin')
            ->setHandler([$this, 'onDeactivatedPlugin'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(1)
            ->execute();

        /* @see \delete_plugins() */
        Register::action()
            ->setGroup('deleted_plugin')
            ->setHandler([$this, 'onDeletedPlugin'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(2)
            ->execute();

        /* @see WP_Upgrader::run() for both plugin upgrades and installation */
        Register::filter()
            ->setGroup('upgrader_install_package_result')
            ->setHandler([$this, 'onInstalledOrUpdatedPlugin'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Fires when a plugin is activated.
     *
     * @see \activate_plugin()
     *
     * @internal
     *
     * @param string|mixed $baseName the plugin basename
     * @return void
     * @throws Exception
     */
    public function onActivatedPlugin($baseName) : void
    {
        $plugin = $this->getPluginFromBaseName($baseName);

        if (! $plugin) {
            return;
        }

        /* @NOTE Unfortunately it is hard to detect activation errors at this stage {unfulvio 2022-02-09} */
        // If we capture object buffering via `ob_get_length()` to detect unexpected output the way `activate_plugin()` does, that doesn't guarantee that the plugin was _not_ activated.
        // If we check for `is_plugin_active()` via `PluginExtension::isActive()`, there might be a race condition where the plugin is still not listed inside the active plugins option and that would produce false positives.
        Events::broadcast($this->buildEvent($plugin, AbstractExtension::ACTION_ACTIVATE));
    }

    /**
     * Fires when a plugin is deactivated.
     *
     * @see \deactivate_plugins()
     *
     * @internal
     *
     * @param string|mixed $baseName the plugin basename
     * @return void
     * @throws Exception
     */
    public function onDeactivatedPlugin($baseName) : void
    {
        $plugin = $this->getPluginFromBaseName($baseName);

        if (! $plugin) {
            return;
        }

        /* @NOTE Generally, plugin deactivation is always successful - more specific cases may need to broadcast an error event outside this class {unfulvio 2022-02-09} */
        Events::broadcast($this->buildEvent($plugin, AbstractExtension::ACTION_DEACTIVATE));
    }

    /**
     * Fires when a plugin is deleted.
     *
     * @see \delete_plugins()
     *
     * @internal
     *
     * @param string|mixed $baseName the plugin basename
     * @param bool|mixed $success whether the plugin was successfully deleted
     * @return void
     * @throws Exception
     */
    public function onDeletedPlugin($baseName, $success) : void
    {
        if (! is_string($baseName) || '' === $baseName) {
            return;
        }

        /* @NOTE We cannot grab the extension via {@see PluginExtension::get()} at this point because it no longer exists in the installation {unfulvio 2022-01-26} */
        $plugin = (new PluginExtension())->setBasename($baseName);

        if ($success) {
            Events::broadcast($this->buildEvent($plugin, AbstractExtension::ACTION_DELETE));
        } else {
            Events::broadcast($this->buildErrorEvent($plugin, AbstractExtension::ACTION_DELETE, 'The plugin could not be deleted.'));
        }
    }

    /**
     * Fires whenever a plugin is installed or updated.
     *
     * Keep in mind that activation is a separate process, and a plugin may be updated without being active.
     *
     * @see WP_Upgrader::run()
     *
     * @internal
     *
     * @param array|WP_Error|mixed $eventData the result of the installation or update
     * @param array|mixed $requestArgs additional arguments
     * @return array|WP_Error|mixed
     * @throws Exception
     */
    public function onInstalledOrUpdatedPlugin($eventData, $requestArgs)
    {
        $action = ArrayHelper::get($requestArgs, 'action', 'update'); // same default as in WordPress

        if (! ArrayHelper::contains(['install', 'update', 'upgrade'], $action)) {
            return $eventData;
        }

        if ('install' !== $action) {
            $this->handlePluginUpdatedEvent($eventData, $requestArgs);
        } elseif ('plugin' === ArrayHelper::get($requestArgs, 'type')) {
            $this->handlePluginInstalledEvent($eventData);
        }

        return $eventData;
    }

    /**
     * Handles a plugin installed event.
     *
     * @param mixed $eventData
     * @return void
     * @throws Exception
     */
    protected function handlePluginInstalledEvent($eventData) : void
    {
        // when installing a new plugin, WordPress doesn't provide the basename and some plugin data may not be easily available, so we use the slug at the minimum
        $slug = ArrayHelper::get($eventData, 'destination_name') ?: ArrayHelper::get($_POST, 'slug');
        $plugin = PluginExtension::getNewInstance()->setSlug(is_string($slug) ? $slug : '');

        if (WordPressRepository::isError($eventData)) {
            Events::broadcast($this->buildErrorEvent($plugin, AbstractExtension::ACTION_INSTALL, $eventData->get_error_message()));
        } elseif (! empty($slug)) {
            Events::broadcast($this->buildEvent($plugin, AbstractExtension::ACTION_INSTALL));
        }
    }

    /**
     * Handles a plugin updated event.
     *
     * @param array|WP_Error|mixed $eventData
     * @param array|mixed $requestArgs
     * @return void
     * @throws Exception
     */
    protected function handlePluginUpdatedEvent($eventData, $requestArgs) : void
    {
        // when updating a plugin, the plugin key should be present, otherwise it is not a plugin
        $baseName = ArrayHelper::get($requestArgs, 'plugin'); // e.g. `hello-dolly/hello-dolly.php`
        $dirPath = ArrayHelper::get($eventData, 'local_destination'); // e.g. `/srv/www/godaddy/public_html/wp-content/plugins`
        $filePath = is_string($baseName) && is_string($dirPath) ? StringHelper::trailingSlash($dirPath).$baseName : ''; // e.g. `/srv/www/godaddy/public_html/wp-content/plugins/hello-dolly/hello-dolly.php`

        if ($plugin = $filePath && $this->canReadPluginFilePath($filePath) ? PluginExtension::get($filePath) : null) {
            if (WordPressRepository::isError($eventData)) {
                Events::broadcast($this->buildErrorEvent($plugin, AbstractExtension::ACTION_UPDATE, $eventData->get_error_message()));
            } else {
                Events::broadcast($this->buildEvent($plugin, AbstractExtension::ACTION_UPDATE));
            }
        }
    }

    /**
     * Determines if a plugin's file path is readable.
     *
     * @param string $filePath
     * @return bool
     */
    protected function canReadPluginFilePath(string $filePath) : bool
    {
        return ! empty($filePath) && is_readable($filePath);
    }

    /**
     * Gets a plugin extension object from a WordPress-supplied base name.
     *
     * @param string|mixed $baseName
     * @return PluginExtension|null
     */
    protected function getPluginFromBaseName($baseName)
    {
        $filePath = is_string($baseName) ? StringHelper::trailingSlash(Configuration::get('wordpress.plugins_directory', '')).$baseName : '';
        $plugin = $this->canReadPluginFilePath($filePath) ? PluginExtension::get($filePath) : null;

        return $plugin ?: null;
    }

    /**
     * Builds a model event for a plugin lifecycle successful action.
     *
     * @param PluginExtension $plugin
     * @param string $action
     * @return PluginLifecycleEvent
     */
    protected function buildEvent(PluginExtension $plugin, string $action) : PluginLifecycleEvent
    {
        return new PluginLifecycleEvent($plugin, $action);
    }

    /**
     * Builds an error event for a plugin lifecycle failed action.
     *
     * @param PluginExtension $plugin
     * @param string $action
     * @param string $errorMessage
     * @return ErrorEvent
     */
    protected function buildErrorEvent(PluginExtension $plugin, string $action, string $errorMessage = '') : ErrorEvent
    {
        return (new ErrorEvent('plugin', $action, $errorMessage))->setResourceData($plugin->toArray());
    }
}

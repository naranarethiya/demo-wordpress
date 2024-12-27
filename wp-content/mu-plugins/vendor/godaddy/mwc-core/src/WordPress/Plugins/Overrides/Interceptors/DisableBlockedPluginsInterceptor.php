<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Plugins\Overrides\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Events\PluginLifecycleEvent;
use GoDaddy\WordPress\MWC\Core\Events\Producers\PluginLifecycleEventsProducer;
use WP_Error;
use WP_Upgrader;

/**
 * Handles lifecycle actions of blocked plugins.
 */
class DisableBlockedPluginsInterceptor extends AbstractInterceptor
{
    /** @var string flag used to display an admin notice when a blocked plugin was activated */
    private $blockedPluginActivatedNoticeFlag = 'mwc_blocked_plugin_activation';

    /** @var string flag used to display an admin notice when a blocked plugin was already active and has now been removed */
    private $blockedPluginUninstalledNoticeFlag = 'mwc_active_blocked_plugin_uninstalled';

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        /* @see WP_Upgrader::install_package() */
        Register::filter()
            ->setGroup('upgrader_pre_install')
            ->setHandler([$this, 'preventInstallBlockedPlugin'])
            ->setArgumentsCount(2)
            ->execute();

        /* @see WP_Upgrader::run() */
        Register::filter()
            ->setGroup('upgrader_install_package_result')
            ->setHandler([$this, 'preventUploadBlockedPlugin'])
            ->setArgumentsCount(2)
            ->execute();

        /* @see activate_plugin() */
        Register::action()
            ->setGroup('activated_plugin')
            ->setHandler([$this, 'onBlockedPluginActivation'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'deactivateBlockedPlugins'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDisplayBlockedPluginAdminNotices'])
            ->execute();
    }

    /**
     * Maybe displays an admin notice after a blocked plugin has been deactivated and/or removed.
     *
     * @internal
     *
     * @return void
     */
    public function maybeDisplayBlockedPluginAdminNotices() : void
    {
        $noticeFlag = ArrayHelper::get($_GET, 'notice');

        if ($this->blockedPluginActivatedNoticeFlag === $noticeFlag) {
            // attempted to newly activate blocked plugin
            $this->displayBlockedPluginAttemptedActivationNotice();
        } elseif ($this->blockedPluginUninstalledNoticeFlag === $noticeFlag) {
            // blocked plugin was already installed
            $this->displayActiveBlockedPluginUninstalledNotice();
        }
    }

    /**
     * Enqueues and displays an admin notice when the merchant attempted to activate a blocked plugin.
     *
     * @return void
     */
    protected function displayBlockedPluginAttemptedActivationNotice() : void
    {
        Notices::enqueueAdminNotice(Notice::getNewInstance()
            ->setId('mwc-blocked-plugin-activation')
            ->setType(Notice::TYPE_WARNING)
            ->setDismissible(false)
            ->setTitle($this->getBlockedPluginAdminNoticeTitle())
            ->setContent($this->getBlockedPluginAdminNoticeMessage()));
    }

    /**
     * Enqueues and displays a notice when one or more active blocked plugins were found and uninstalled.
     *
     * @return void
     */
    protected function displayActiveBlockedPluginUninstalledNotice() : void
    {
        Notices::enqueueAdminNotice(Notice::getNewInstance()
            ->setId('mwc-active-blocked-plugin-uninstalled')
            ->setType(Notice::TYPE_WARNING)
            ->setDismissible(false)
            ->setTitle($this->getBlockedPluginUninstalledAdminNoticeTitle())
            ->setContent($this->getBlockedPluginAdminNoticeMessage()));
    }

    /**
     * Gets the message title for the admin notice to be displayed upon installation or activation of a blocked plugin.
     *
     * @return string
     */
    protected function getBlockedPluginAdminNoticeTitle() : string
    {
        return __('You attempted to install a blocked plugin.', 'mwc-core');
    }

    /**
     * Gets the message title for the admin notice to be displayed up detection of one or more already-installed blocked plugins.
     *
     * @return string
     */
    protected function getBlockedPluginUninstalledAdminNoticeTitle() : string
    {
        return __('One or more blocked plugins have been removed.', 'mwc-core');
    }

    /**
     * Gets the message content for the admin notice to be displayed upon installation or activation of a blocked plugin.
     *
     * @return string
     */
    protected function getBlockedPluginAdminNoticeMessage() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - Opening <a> tag, %2$s - Closing </a> tag */
            __('GoDaddy disallows a small list of plugins that can cause performance and other issues on your store. If you attempt to install them, we\'ll remove them for you. You can check out %1$sthe entire plugin blocklist here%2$s.', 'mwc-core'),
            '<a href="https://godaddy.com/help/a-41567" target="_blank">',
            '</a>'
        );
    }

    /**
     * Prevents installing a blocked plugin.
     *
     * @see WP_Upgrader::install_package()
     * @internal
     *
     * @param bool|mixed $installResult installation response
     * @param array<mixed>|mixed $args
     * @return bool|mixed
     */
    public function preventInstallBlockedPlugin($installResult, $args)
    {
        if (! $pluginSlug = (string) ArrayHelper::get($_POST, 'slug')) {
            return $installResult;
        }

        // For the sake of this filter we can assume the basename is in this format. The isBlocked() check will strip everything after the `/` anyway.
        $plugin = PluginExtension::getNewInstance()->setBasename("{$pluginSlug}/{$pluginSlug}.php")->setSlug($pluginSlug);

        try {
            if ($plugin->isBlocked()) {
                $installResult = new WP_Error('blocked_plugin', $this->getBlockedPluginAdminNoticeTitle().' '.$this->getBlockedPluginAdminNoticeMessage());

                /*
                 * we need to manually fire an event here because the event producer will not trigger one with all our desired data under these circumstances
                 * @see PluginLifecycleEventsProducer::handlePluginInstalledEvent()
                 */
                Events::broadcast(PluginLifecycleEvent::getNewInstance($plugin, AbstractExtension::ACTION_INSTALL));
            }
        } catch (Exception $e) {
            // catch exceptions in a hook callback
        }

        return $installResult;
    }

    /**
     * Prevents uploading a blocked plugin.
     *
     * @see WP_Upgrader::run()
     * @internal
     *
     * @param array|WP_Error|mixed $installResult from {@see WP_Upgrader::install_package()}
     * @param array<mixed>|mixed $args
     * @return array|WP_Error|mixed
     */
    public function preventUploadBlockedPlugin($installResult, $args)
    {
        if (WordPressRepository::isError($installResult)) {
            return $installResult;
        }

        if (! $pluginSlug = ArrayHelper::get($installResult, 'destination_name')) {
            return $installResult;
        }

        try {
            $plugin = PluginExtension::getByDirectoryName($pluginSlug);
            if ($plugin && $plugin->isBlocked()) {
                $plugin->uninstall();

                $installResult = new WP_Error('plugin_blocked', '<div class="notice notice-error inline"><p>'.$this->getBlockedPluginAdminNoticeTitle().' '.$this->getBlockedPluginAdminNoticeMessage().'</p></div>');
            }
        } catch (Exception $e) {
            // catch exceptions in hook callbacks
        }

        return $installResult;
    }

    /**
     * Deactivates and uninstalls a blocked plugin that was just activated.
     *
     * @internal
     *
     * @param string|mixed $pluginBasename path to the plugin file relative to the plugins directory
     * @return void
     */
    public function onBlockedPluginActivation($pluginBasename) : void
    {
        $plugin = PluginExtension::getNewInstance()->setBasename($pluginBasename);

        try {
            if ($plugin->isBlocked()) {
                $plugin->uninstall();

                if (! $this->headersSent()) {
                    Redirect::to('plugins.php')
                        ->setQueryParameters(['notice' => $this->blockedPluginActivatedNoticeFlag])
                        ->execute();
                }
            }
        } catch (Exception $e) {
            // catch exceptions in a hook callback
        }
    }

    /**
     * Determines whether any HTTP headers have already been sent.
     *
     * @return bool
     */
    protected function headersSent() : bool
    {
        return headers_sent();
    }

    /**
     * Deactivates and uninstalls plugins that should be blocked.
     *
     * Same {@see PluginExtension::isBlocked()} logic but we don't call that directly to avoid building a potentially large number of objects and querying plugin data via {@see PluginExtension::get()} for each active plugin.
     *
     * @internal
     *
     * @throws Exception
     * @return void
     */
    public function deactivateBlockedPlugins() : void
    {
        $hasBlockedPlugin = false;

        try {
            $blockedPlugins = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getBlockedPlugins();

            if (! $blockedPlugins) {
                return;
            }

            $activePlugins = ArrayHelper::wrap(get_option('active_plugins', []));

            foreach ($activePlugins as $activePlugin) {
                if (! ArrayHelper::contains($blockedPlugins, StringHelper::before($activePlugin, '/'))) {
                    continue;
                }

                $plugin = PluginExtension::getNewInstance()->setBasename($activePlugin);
                // this is to make sure the active_plugins option is updated before uninstalling to avoid potential recursive loops
                $plugin->deactivate();
                $plugin->uninstall();

                $hasBlockedPlugin = true;
            }
        } catch (Exception $exception) {
            // since we are in a hook callback context we need to catch the exception instead of throwing
        }

        if ($hasBlockedPlugin) {
            if (! $this->headersSent()) {
                Redirect::to('plugins.php')
                    ->setQueryParameters(['notice' => $this->blockedPluginUninstalledNoticeFlag])
                    ->execute();
            } else {
                $this->displayActiveBlockedPluginUninstalledNotice();
            }
        }
    }
}

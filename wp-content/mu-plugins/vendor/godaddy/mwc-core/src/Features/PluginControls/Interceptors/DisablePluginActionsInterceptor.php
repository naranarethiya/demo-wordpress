<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Disables plugin actions from firing on locked plugins.
 */
class DisablePluginActionsInterceptor extends AbstractLockedPluginInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('load-plugins.php')
            ->setHandler([$this, 'maybeStopBulkAction'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        $this->registerIndividualPluginActionHooks();
    }

    /**
     * Registers hook handlers to stop individual plugin actions.
     *
     * @throws Exception
     */
    protected function registerIndividualPluginActionHooks() : void
    {
        foreach (['deactivate_plugin', 'delete_plugin', 'pre_uninstall_plugin'] as $hook) {
            Register::action()
                ->setGroup($hook)
                ->setHandler([$this, 'maybeStopAction'])
                ->setPriority(PHP_INT_MIN)
                ->execute();
        }
    }

    /**
     * Stops actions for locked plugins in bulk list from request parameter.
     *
     * @return void
     */
    public function maybeStopBulkAction() : void
    {
        $checkedPlugins = ArrayHelper::get(ArrayHelper::wrap($_REQUEST), 'checked', []);
        if (! is_array($checkedPlugins) || empty($checkedPlugins)) {
            return;
        }

        foreach ($this->getLockedPlugins() as $plugin) {
            $basename = ArrayHelper::get(ArrayHelper::wrap($plugin), 'basename', '');

            if (is_string($basename) && ArrayHelper::contains($checkedPlugins, $basename)) {
                $this->die($this->getBlockedActionMessage($this->getLockedPluginName($basename) ?: $basename));
            }
        }
    }

    /**
     * Stops actions for locked plugins.
     *
     * @param mixed $basename The plugin basename to check against.
     */
    public function maybeStopAction($basename) : void
    {
        if (! $basename || ! is_string($basename)) {
            return;
        }

        if (! $this->isPluginLocked($basename)) {
            return;
        }

        $this->die($this->getBlockedActionMessage($this->getLockedPluginName($basename) ?: $basename));
    }

    /**
     * Generates the blocked plugin's action message.
     *
     * @param string $pluginName
     * @return string
     */
    protected function getBlockedActionMessage(string $pluginName) : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - plugin name or basename */
            __('Sorry, you are not allowed to deactivate, uninstall, or delete %1$s for this site.', 'woosaas-app'),
            $pluginName
        );
    }
}

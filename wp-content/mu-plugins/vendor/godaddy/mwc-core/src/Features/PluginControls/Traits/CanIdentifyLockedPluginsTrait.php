<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Traits;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

trait CanIdentifyLockedPluginsTrait
{
    /**
     * Determines whether the plugin with the given basename is one of the locked plugins.
     *
     * @param string $basename
     *
     * @return bool
     */
    public function isPluginLocked(string $basename) : bool
    {
        foreach ($this->getLockedPlugins() as $plugin) {
            if ($basename === ArrayHelper::get((array) $plugin, 'basename')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the information for the locked plugins.
     *
     * @return array<mixed>
     */
    protected function getLockedPlugins() : array
    {
        return (array) Configuration::get('wordpress.plugins.locked');
    }

    /**
     * Gets the name of the plugin with the given basename.
     *
     * If the plugin with the given basename is not a locked plugin, the method returns null.
     *
     * @param string $basename
     *
     * @return string|null
     */
    protected function getLockedPluginName(string $basename) : ?string
    {
        foreach ($this->getLockedPlugins() as $plugin) {
            if ($basename === ArrayHelper::get((array) $plugin, 'basename')) {
                $pluginName = ArrayHelper::get((array) $plugin, 'name', '');

                return is_string($pluginName) ? $pluginName : '';
            }
        }

        return null;
    }
}

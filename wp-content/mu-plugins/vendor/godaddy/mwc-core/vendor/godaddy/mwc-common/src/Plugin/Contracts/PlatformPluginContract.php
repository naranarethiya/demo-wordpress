<?php

namespace GoDaddy\WordPress\MWC\Common\Plugin\Contracts;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;

/**
 * Platform Plugin Contract.
 */
interface PlatformPluginContract extends ComponentContract
{
    /**
     * Returns the list of configuration directories for this platform plugin.
     *
     * @return string[] List of relative configuration directory paths.
     */
    public function getConfigurationDirectories() : array;

    /**
     * Gets the absolute paths of the configuration directories for this platform plugin.
     *
     * @return string[] List of absolute configuration directory paths.
     */
    public function getAbsolutePathOfConfigurationDirectories() : array;

    /**
     * Actions that this contract should do just after configuration is loaded.
     *
     * @return void
     */
    public function onConfigurationLoaded();
}

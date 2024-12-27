<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors;

use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Traits\CanIdentifyLockedPluginsTrait;

/**
 * Base class for interceptors used to prevent users from modifying locked plugins.
 */
abstract class AbstractLockedPluginInterceptor extends AbstractInterceptor
{
    use CanIdentifyLockedPluginsTrait;

    /**
     * Gets a comma separated list of plugins that have one of the given basenames and are locked.
     *
     * @param string[] $basenames
     *
     * @return string|null
     */
    protected function prepareLockedPluginNames(array $basenames) : ?string
    {
        $names = array_filter(array_map(fn ($basename) => $this->getLockedPluginName($basename), $basenames));

        return empty($names) ? null : implode(', ', $names);
    }

    /**
     * A convenience wrapper for wp_die().
     *
     * @param string $message
     * @return void
     */
    protected function die(string $message) : void
    {
        if (function_exists('wp_die')) {
            wp_die($message);
        }

        die($message);
    }
}

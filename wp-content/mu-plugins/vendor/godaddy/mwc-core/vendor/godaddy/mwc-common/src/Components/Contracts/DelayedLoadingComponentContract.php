<?php

namespace GoDaddy\WordPress\MWC\Common\Components\Contracts;

/**
 * A delayed loading component represents functionality that can be loaded later.
 */
interface DelayedLoadingComponentContract extends ComponentContract
{
    /**
     * Schedules a callback to load the component.
     *
     * @param callable $callback
     */
    public static function scheduleLoading(callable $callback) : void;
}

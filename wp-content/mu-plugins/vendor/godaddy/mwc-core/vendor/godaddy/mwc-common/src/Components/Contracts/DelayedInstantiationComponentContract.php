<?php

namespace GoDaddy\WordPress\MWC\Common\Components\Contracts;

/**
 * A delayed instantiation component represents functionality that can be instantiated later.
 */
interface DelayedInstantiationComponentContract extends ComponentContract
{
    /**
     * Schedules a callback to instantiate the component.
     *
     * @param callable $callback
     */
    public static function scheduleInstantiation(callable $callback) : void;
}

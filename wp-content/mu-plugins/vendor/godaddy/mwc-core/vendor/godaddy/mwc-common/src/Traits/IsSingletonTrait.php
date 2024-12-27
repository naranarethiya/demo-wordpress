<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use ReflectionClass;

/**
 * A trait for singletons.
 */
trait IsSingletonTrait
{
    /** @var ?static holds the current singleton instance */
    protected static $instance;

    /**
     * Determines if the current instance is loaded.
     *
     * @return bool
     */
    public static function isLoaded() : bool
    {
        return (bool) static::$instance;
    }

    /**
     * Gets the singleton instance.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (! static::$instance) {
            static::$instance = (new ReflectionClass(static::class))->newInstanceArgs(func_get_args());
        }

        return static::$instance;
    }

    /**
     * Resets the singleton instance.
     *
     * @return void
     */
    public static function reset() : void
    {
        static::$instance = null;
    }
}

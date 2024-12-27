<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use ReflectionClass;

/**
 * A trait that allows a given class/object to get a new instance of itself.
 */
trait CanGetNewInstanceTrait
{
    /**
     * Creates and returns a new instance of the calling class.
     *
     * @return static
     */
    public static function getNewInstance()
    {
        return (new ReflectionClass(static::class))->newInstanceArgs(func_get_args());
    }
}

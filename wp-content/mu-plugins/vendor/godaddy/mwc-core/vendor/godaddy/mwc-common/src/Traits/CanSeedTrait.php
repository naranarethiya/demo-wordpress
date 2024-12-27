<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait that allows a class to get a new instance of itself and populate values.
 */
trait CanSeedTrait
{
    use CanBulkAssignPropertiesTrait;
    use CanGetNewInstanceTrait;

    /**
     * Seeds a new instance of an object without saving it.
     *
     * @param array $data property values
     * @return static
     */
    public static function seed(array $data = [])
    {
        return static::getNewInstance()->setProperties($data);
    }
}

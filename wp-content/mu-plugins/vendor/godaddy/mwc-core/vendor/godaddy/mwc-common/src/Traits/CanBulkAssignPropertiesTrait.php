<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use ReflectionClass;

/**
 * A trait for objects where properties could be set in bulk.
 */
trait CanBulkAssignPropertiesTrait
{
    /**
     * Sets all class properties that have setter methods using the given data.
     *
     * @param array<string, mixed> $data property values
     * @return static
     */
    public function setProperties(array $data)
    {
        foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
            if (! ArrayHelper::exists($data, $property->getName())) {
                continue;
            }

            if (method_exists($this, 'set'.ucfirst($property->getName()))) {
                $this->{'set'.ucfirst($property->getName())}(ArrayHelper::get($data, $property->getName()));
            }
        }

        return $this;
    }
}

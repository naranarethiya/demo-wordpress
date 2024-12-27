<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use ReflectionClass;
use ReflectionProperty;

/**
 * A trait that allows a given class/object to convert its state to an array.
 */
trait CanConvertToArrayTrait
{
    /** @var bool convert Private Properties to Array Output */
    protected $toArrayIncludePrivate = false;

    /** @var bool convert Protected Properties to Array Output */
    protected $toArrayIncludeProtected = true;

    /** @var bool convert Public Properties to Array Output */
    protected $toArrayIncludePublic = true;

    /** @var bool prevents infinite recursive calls */
    private $bailIfInRecursiveCall = false;

    /**
     * Converts all class data properties to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        if ($this->bailIfInRecursiveCall) {
            return [];
        }

        $this->bailIfInRecursiveCall = true;

        $array = [];

        foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
            if ($this->toArrayShouldPropertyBeAccessible($property)) {
                $property->setAccessible(true);

                $propertyValue = $property->getValue($this);

                $value = $propertyValue;

                if ($this->canConvertItemToArray($propertyValue)) {
                    /** @phpstan-ignore-next-line PhpStan might not understand that the check above means the object has a toArray() method */
                    $value = $propertyValue->toArray();
                } elseif (ArrayHelper::accessible($value)) {
                    $trait = $this;
                    /* @phpstan-ignore-next-line */
                    array_walk($value, static function (&$item) use ($trait) {
                        $item = $trait->canConvertItemToArray($item) ? $item->toArray() : $item;
                    });
                }

                $array[$property->getName()] = $value;
            }
        }

        $this->bailIfInRecursiveCall = false;

        return ArrayHelper::except($array, [
            'bailIfInRecursiveCall',
            'toArrayIncludePrivate',
            'toArrayIncludeProtected',
            'toArrayIncludePublic',
        ]);
    }

    /**
     * Determines if an item can be converted to an array.
     *
     * @param mixed $item
     * @return bool
     */
    protected function canConvertItemToArray($item) : bool
    {
        return is_object($item) && is_callable([$item, 'toArray']);
    }

    /**
     * Checks if the property is accessible for {@see toArray()} conversion.
     *
     * @param ReflectionProperty $property
     * @return bool
     */
    private function toArrayShouldPropertyBeAccessible(ReflectionProperty $property) : bool
    {
        // Force accessible for typed properties in PHP <8.1 to avoid an exception from isInitialized()
        $property->setAccessible(true);

        if (! $property->isInitialized($this)) {
            return false;
        }

        if ($this->toArrayIncludePublic && $property->isPublic()) {
            return true;
        }

        if ($this->toArrayIncludeProtected && $property->isProtected()) {
            return true;
        }

        if ($this->toArrayIncludePrivate && $property->isPrivate()) {
            return true;
        }

        return false;
    }
}

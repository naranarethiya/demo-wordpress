<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

/**
 * An helper for handling strict types.
 */
class TypeHelper
{
    /**
     * Returns the string value or default.
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public static function string($value, string $default) : string
    {
        return is_string($value) ? $value : $default;
    }

    /**
     * Returns the given value if it is string or null otherwise.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function stringOrNull($value) : ?string
    {
        return is_string($value) ? $value : null;
    }

    /**
     * Returns the given value if it is a non-empty string or null otherwise.
     *
     * @param mixed $value
     * @return non-empty-string|null
     */
    public static function nonEmptyStringOrNull($value) : ?string
    {
        return static::string($value, '') ?: null;
    }

    /**
     * Returns the __toString() representation of an object or null.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function objectStringOrNull($value) : ?string
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            return $value->__toString();
        }

        return null;
    }

    /**
     * Returns the value as a string.
     *
     * @param mixed $value
     * @return string
     */
    public static function ensureString($value) : string
    {
        $objectAsString = static::objectStringOrNull($value);
        $value = static::scalar($objectAsString ?? $value, '');

        return static::string($value, (string) $value);
    }

    /**
     * Returns the array value or default.
     *
     * @param mixed $value
     * @param array<mixed> $default
     * @return array<mixed>
     */
    public static function array($value, array $default) : array
    {
        return ArrayHelper::accessible($value) ? $value : $default;
    }

    /**
     * Returns the array value or default.
     *
     * @param mixed $value
     * @return array<mixed>
     */
    public static function ensureArray($value) : array
    {
        if (ArrayHelper::accessible($value)) {
            return $value;
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            if (method_exists($value, 'to_array')) {
                return $value->to_array();
            }
        }

        return (array) $value;
    }

    /**
     * Returns an array of instances of the given type.
     *
     * @template T
     * @param mixed $array
     * @param class-string<T> $type a classname used to filter the values in the array
     * @param bool $maintainIndex
     * @return T[]
     */
    public static function arrayOf($array, string $type, bool $maintainIndex = true) : array
    {
        $array = array_filter(static::array($array, []), static function ($item) use ($type) {
            return $item instanceof $type;
        });

        return $maintainIndex ? $array : array_values($array);
    }

    /**
     * Returns an array of class-string values of the given type.
     *
     * @template T of object
     * @param mixed $array
     * @param class-string<T> $type a classname used to filter the values in the array
     * @param bool $maintainIndex
     * @return class-string<T>[]
     */
    public static function arrayOfClassStrings($array, string $type, bool $maintainIndex = true) : array
    {
        $array = array_filter(static::array($array, []), function ($item) use ($type) {
            return is_string($item) && is_a($item, $type, true);
        });

        return $maintainIndex ? $array : array_values($array);
    }

    /**
     * Returns an array of strings.
     *
     * @param mixed $array
     * @param bool $maintainIndex
     * @return string[]
     */
    public static function arrayOfStrings($array, bool $maintainIndex = true) : array
    {
        $array = array_filter(static::array($array, []), static function ($item) {
            return is_string($item);
        });

        return $maintainIndex ? $array : array_values($array);
    }

    /**
     * Returns an array of integers.
     *
     * @param mixed $array
     * @param bool $maintainIndex
     *
     * @return int[]
     */
    public static function arrayOfIntegers($array, bool $maintainIndex = true) : array
    {
        $finalArray = [];

        foreach (static::array($array, []) as $index => $item) {
            if (0 === $item || '0' === $item) {
                $finalArray[$index] = (int) $item;
                continue;
            }

            if ($item = static::int($item, 0)) {
                $finalArray[$index] = $item;
            }
        }

        return $maintainIndex ? $finalArray : array_values($finalArray);
    }

    /**
     * Returns the integer value or default.
     *
     * @param mixed $value
     * @param int $default
     *
     * @return int
     */
    public static function int($value, int $default) : int
    {
        return is_int($value) || (is_numeric($value) && ctype_digit((string) $value)) ? (int) $value : $default;
    }

    /**
     * Returns the float value or default.
     *
     * @param mixed $value
     * @param float $default
     * @return float
     */
    public static function float($value, float $default) : float
    {
        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Returns the boolean value or default.
     *
     * @param mixed $value
     * @param bool $default
     * @return bool
     */
    public static function bool($value, bool $default) : bool
    {
        return is_bool($value) ? $value : $default;
    }

    /**
     * Returns the scalar value or default.
     *
     * @param mixed $value
     * @param bool|float|int|string $default
     * @return bool|float|int|string
     */
    public static function scalar($value, $default)
    {
        return is_scalar($value) ? $value : $default;
    }
}

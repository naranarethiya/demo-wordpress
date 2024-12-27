<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use ArrayAccess;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * A helper to manipulate arrays.
 */
class ArrayHelper
{
    /**
     * Determines if a given item is an accessible array.
     *
     * @param mixed $value
     * @return bool
     * @phpstan-return ($value is array ? true : false)
     */
    public static function accessible($value) : bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Combines two array values.
     *
     * @NOTE this won't work with instances of {@see ArrayAccess}
     *
     * @param mixed $array original array - will throw an exception when non-arrays are passed
     * @param mixed $arrays variable list of arrays to merge - if one of them is not accessible an exception is thrown
     * @return mixed[]
     * @throws BaseException
     */
    public static function combine($array, ...$arrays) : array
    {
        if ($array instanceof ArrayAccess) {
            throw new BaseException('The array provided as the original array must be a native array and not an instance of ArrayAccess.');
        }

        if (! is_array($array)) {
            throw new BaseException('The array provided as the original array was not accessible.');
        }

        $merge = [];

        foreach ($arrays as $item) {
            if ($item instanceof ArrayAccess) {
                throw new BaseException('One of the arrays provided to merge into the original array is not a native array: instance of ArrayAccess provided instead.');
            }

            if (! is_array($item)) {
                throw new BaseException('One of the arrays provided to merge into the original array was not accessible.');
            }

            $merge[] = $item;
        }

        return array_merge($array, ...$merge);
    }

    /**
     * Combines two array values recursively to preserve nested keys.
     *
     * @NOTE this won't work with instances of {@see ArrayAccess}
     *
     * @param mixed $array original array - will throw an exception when non-arrays are passed
     * @param mixed $arrays variable list of arrays to merge - if one of them is not accessible an exception is thrown
     * @return mixed[]
     * @throws BaseException
     */
    public static function combineRecursive($array, ...$arrays) : array
    {
        if ($array instanceof ArrayAccess) {
            throw new BaseException('The array provided as the original array must be a native array and not an instance of ArrayAccess.');
        }

        if (! is_array($array)) {
            throw new BaseException('The array provided as the original array was not accessible.');
        }

        $merge = [];

        foreach ($arrays as $item) {
            if ($item instanceof ArrayAccess) {
                throw new BaseException('One of the arrays provided to merge into the original array is not a native array: instance of ArrayAccess provided instead.');
            }

            if (! is_array($item)) {
                throw new BaseException('One of the arrays provided to merge into the original array was not accessible.');
            }

            $merge[] = $item;
        }

        return array_replace_recursive($array, ...$merge);
    }

    /**
     * Determines if an array has a given value.
     *
     * @param array $array
     * @param mixed $value
     * @return bool
     */
    public static function contains(array $array, $value) : bool
    {
        return self::exists(array_flip(self::flatten($array)), $value);
    }

    /**
     * Gets an array excluding the given keys.
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    public static function except(array $array, $keys) : array
    {
        $temp = $array;

        self::remove($temp, self::wrap($keys));

        return $temp;
    }

    /**
     * Determines if an array key exists.
     *
     * @param ArrayAccess|array<mixed> $array
     * @param string|int $key
     * @return bool
     */
    public static function exists($array, $key) : bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, self::wrap($array));
    }

    /**
     * Flattens a multi-dimensional array.
     *
     * @param array $array
     * @return array
     */
    public static function flatten(array $array) : array
    {
        $arrayValues = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                $arrayValues = array_merge($arrayValues, self::flatten($value));

                continue;
            }

            $arrayValues[] = $value;
        }

        return $arrayValues;
    }

    /**
     * Flattens a multi-dimensional array to dot notated keys.
     *
     * @param array $array
     * @param bool $ignoreEndIterativeKeys
     * @return array
     */
    public static function flattenToDotNotation(array $array, bool $ignoreEndIterativeKeys = false) : array
    {
        $result = [];
        $keys = [];
        $depthOffset = 1;
        $arrayIterator = new RecursiveArrayIterator($array);
        $iterator = new RecursiveIteratorIterator($arrayIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $key => $value) {
            $keys[$iterator->getDepth()] = $key;

            if (! self::accessible($value) || [] === $value) {
                if ($ignoreEndIterativeKeys && is_int(end($keys))) {
                    $depthOffset = 0;
                }

                $dotKey = implode('.', array_slice($keys, 0, $iterator->getDepth() + $depthOffset));
                $result[$dotKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Find the last value within the given haystack array that matches any of the given values.
     *
     * @template TValue
     * @template TDefaultValue
     *
     * @param TValue[] $haystack The array.
     * @param mixed[] $needles The searched values.
     * @param TDefaultValue $default
     *
     * @return TValue|TDefaultValue
     */
    public static function findLastMatch(array $haystack, array $needles, $default = null)
    {
        $matches = array_intersect($haystack, $needles);

        $lastMatchKey = array_key_last($matches);

        if ($lastMatchKey !== null) {
            return $matches[$lastMatchKey];
        }

        return $default;
    }

    /**
     * Gets an array value from a dot notated key.
     *
     * @param mixed $array
     * @param int|string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! self::accessible($array)) {
            return $default;
        }

        if (self::exists($array, $key)) {
            return $array[$key];
        }

        foreach (explode('.', (string) $key) as $segment) {
            if (! self::exists($array, $segment)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Determines if an array has a nested key by dot notation.
     *
     * @param ArrayAccess|array<mixed> $array
     * @param string|string[] $keys
     * @return bool
     */
    public static function has($array, $keys) : bool
    {
        $keys = self::wrap($keys);

        // @TODO: Remove when PHP 8 is the minimum version and can support multiple function parameter type casting
        if (! $array || empty($keys)) {
            return false;
        }

        foreach ($keys as $key) {
            if (self::exists($array, $key)) {
                continue;
            }

            $subArray = $array;

            foreach (explode('.', $key) as $segment) {
                if (! self::accessible($subArray) || ! self::exists($subArray, $segment)) {
                    return false;
                }

                $subArray = $subArray[$segment];
            }
        }

        return true;
    }

    /**
     * Takes an array and outputs its values indexed by keys generated by the given callable.
     * If $indexer returns null, the value will be excluded from the indexed array.
     *
     * @template TValue
     * @template TIndex of int|string
     * @param TValue[] $array The array to index
     * @param callable(TValue) : ?TIndex $indexer Receives a value from the array, returns a string|int by which to index that value.
     * @return array<TIndex, TValue>
     */
    public static function indexBy(array $array, callable $indexer) : array
    {
        $indexedArray = [];

        foreach ($array as $value) {
            $index = $indexer($value);

            if (null !== $index) {
                $indexedArray[$index] = $value;
            }
        }

        return $indexedArray;
    }

    /**
     * Inserts the given element before or after the given key or value in the array.
     *
     * Allows inserting one or more elements, with or without keys. When inserting into a positional array, a value instead
     * of a key should be given. If the given key or value is not present in the array, it's possible to optionally
     * append the given element(s) to the array.
     *
     * @param array $array the array to insert the element to
     * @param mixed $element the element(s) to insert to the array
     * @param int|string $keyOrValue the key or value after which to insert the element
     * @param bool $after optional - whether to insert the element(s) before or after the given key/value
     * @param bool $searchByKey optional - whether to force search for the $keyOrValue in the array's keys (useful with indexed arrays)
     * @return mixed[]
     * @throws BaseException
     */
    public static function insert(array $array, $element, $keyOrValue, bool $after = true, bool $searchByKey = false) : array
    {
        $keys = array_keys($array);
        $isAssoc = self::isAssoc($array);
        $index = array_search($keyOrValue, $searchByKey || $isAssoc ? $keys : array_values($array), true);

        if ($index !== false) {
            $index = $after ? $index + 1 : $index;

            if ($isAssoc) {
                // union will simply append new keys/values to the preceding array if the key is not already present - which is why it won't
                // work for non-assoc arrays
                return array_slice($array, 0, $index) + (is_array($element) ? $element : [$element]) + array_slice($array, $index);
            }

            array_splice($array, $index, 0, $element);

            return $array;
        }

        // if the key/value was not found, append elements to the array
        $array = self::combine($array, self::wrap($element));

        return $array;
    }

    /**
     * Inserts the given element before the given value in the array.
     *
     * @param mixed[] $array the array to insert the element to
     * @param mixed $element the element(s) to insert to the array
     * @param int|string $value the value after which to insert the element
     * @return mixed[]
     * @throws BaseException
     */
    public static function insertBefore(array $array, $element, $value) : array
    {
        return self::insert($array, $element, $value, false);
    }

    /**
     * Inserts the given element after the given value in the array.
     *
     * @param mixed[] $array the array to insert the element to
     * @param mixed $element the element(s) to insert to the array
     * @param int|string $value the value after which to insert the element
     * @return mixed[]
     * @throws BaseException
     */
    public static function insertAfter(array $array, $element, $value) : array
    {
        return self::insert($array, $element, $value);
    }

    /**
     * Inserts the given element before the given key in the array.
     *
     * @param mixed[] $array the array to insert the element to
     * @param mixed $element the element(s) to insert to the array
     * @param int|string $key the key after which to insert the element
     * @return array<int|string, mixed>
     * @throws BaseException
     */
    public static function insertBeforeKey(array $array, $element, $key) : array
    {
        return self::insert($array, $element, $key, false, true);
    }

    /**
     * Inserts the given element after the given key in the array.
     *
     * @param mixed[] $array the array to insert the element to
     * @param mixed $element the element(s) to insert to the array
     * @param int|string $key the key after which to insert the element
     * @return mixed[]
     * @throws BaseException
     */
    public static function insertAfterKey(array $array, $element, $key) : array
    {
        return self::insert($array, $element, $key, true, true);
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param array $array
     * @return bool
     */
    public static function isAssoc(array $array) : bool
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Encodes an array to JSON.
     *
     * @param ArrayAccess|array $array
     * @return string
     */
    public static function jsonEncode(array $array) : string
    {
        return json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Plucks values from an array given a key with optional key assignment.
     *
     * @NOTE: The WordPress function {@see wp_list_pluck()} does not support multidimensional arrays in a standard way.
     *
     * @param array<mixed>|ArrayAccess $array
     * @param string|array<mixed>|int $search
     * @return array
     */
    public static function pluck($array, $search) : array
    {
        $results = [];

        if (! is_iterable($array)) {
            return $results;
        }

        foreach ($array as $item) {
            /** @var int|string $search */
            if ($value = self::get($item, $search)) {
                $results[] = $value;
            }
        }

        return $results;
    }

    /**
     * Converts the array into a query string.
     *
     * @NOTE: We use a custom function here instead of {@see add_query_arg()} because the WordPress function appends items to the current or given url.
     * That can cause problems when using this class for non-standard WordPress redirects.
     * This function uses the native {@see http_build_query()} instead.
     *
     * @param array $array
     * @return string
     */
    public static function query(array $array) : string
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Removes a given key or keys from the original array.
     *
     * @param array $array
     * @param array|string $keys
     */
    public static function remove(array &$array, $keys)
    {
        $original = &$array;

        foreach (self::wrap($keys) as $key) {
            // if the key exists at this level unset and bail
            if (self::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Sets an array value from dot notated key.
     *
     * @param ArrayAccess|array $array
     * @param string $search
     * @param mixed $value
     * @return mixed|void|null
     */
    public static function set(&$array, string $search, $value = null)
    {
        if (! self::accessible($array)) {
            return;
        }

        foreach (explode('.', $search) as $segment) {
            if (! self::exists($array, $segment)) {
                $array[$segment] = [];
            }

            $array = &$array[$segment];
        }

        return $array = $value;
    }

    /**
     * Filters a given array by its callback.
     *
     * @param array $array
     * @param callable $callback
     * @param bool $maintainIndex
     *
     * @return array
     */
    public static function where(array $array, callable $callback, bool $maintainIndex = true) : array
    {
        $array = array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);

        return $maintainIndex ? $array : array_values($array);
    }

    /**
     * Filters a given array removing any entries that have a null value.
     *
     * @param array $array
     * @param bool $maintainIndex
     * @return array
     */
    public static function whereNotNull(array $array, bool $maintainIndex = true) : array
    {
        return static::where(
            $array,
            static function ($value) {
                return ! is_null($value);
            },
            $maintainIndex
        );
    }

    /**
     * Wraps a given item in an array if it is not an array.
     *
     * @param mixed $item
     * @return array
     */
    public static function wrap($item = null) : array
    {
        if (is_array($item)) {
            return $item;
        }

        return $item ? [$item] : [];
    }

    /**
     * Joins the array elements into a string using natural language.
     *
     * For example, the array `['US', 'Canada', 'Mexico']` would become `'US, Canada, and Mexico'`.
     *
     * When using this method to create user-facing text, it is recommended to supply a localized conjunction.
     *
     * @param array<scalar> $array
     * @param string|null $conjunction one of 'and' or 'or'
     * @param string|null $pattern a custom sprintf pattern, with placeholders %1$s and %2$s
     * @return string
     */
    public static function joinNatural(array $array, ?string $conjunction = 'and', ?string $pattern = '') : string
    {
        $last = array_pop($array);

        if ($array) {
            if (! $pattern) {
                switch ($conjunction) {
                    case 'or':
                        /* translators: A list of items, for example: "US or Canada", or "US, Canada, or Mexico". English uses Oxford comma before the conjunction ("or") if there are at least 2 items preceding it - hence the use of plural forms. If your locale does not use Oxford comma, you can just provide the same translation to all plural forms. Placeholders: %1$s - a comma-separated list of item, %2$s - the final item in the list */
                        $pattern = _n('%1$s or %2$s', '%1$s, or %2$s', count($array), 'mwc-common');
                        break;

                    case 'and':
                    default:
                        /* translators: A list of items, for example: "US and Canada", or "US, Canada, and Mexico". English uses Oxford comma before the conjunction ("and") if there are at least 2 items preceding it - hence the use of plural forms. If your locale does not use Oxford comma, you can just provide the same translation to all plural forms. Placeholders: %1$s - a comma-separated list of items, %2$s - the final item in the list */
                        $pattern = _n('%1$s and %2$s', '%1$s, and %2$s', count($array), 'mwc-common');
                        break;
                }
            }

            return sprintf($pattern, implode(', ', $array), $last);
        }

        return TypeHelper::string($last, '');
    }

    /**
     * Gets a string value from the given array.
     *
     * Returns the given default string if the value cannot be converted to string.
     *
     * @param array<string, mixed> $array
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function getStringValueForKey(array $array, string $key, string $default = '') : string
    {
        return TypeHelper::string(static::get($array, $key), $default);
    }

    /**
     * Gets an array value from the given array.
     *
     * Returns the given default if the value cannot be converted to an array.
     *
     * @param array<string, mixed> $array
     * @param string $key
     * @param array<mixed> $default
     * @return array<mixed>
     */
    public static function getArrayValueForKey(array $array, string $key, array $default = []) : array
    {
        return TypeHelper::array(static::get($array, $key), $default);
    }

    /**
     * Gets an integer value from the given array.
     *
     * Returns the given default if the value cannot be converted to an integer.
     *
     * @param array<string, mixed>  $array
     * @param string $key
     * @param int    $default
     * @return int
     */
    public static function getIntValueForKey(array $array, string $key, int $default = 0) : int
    {
        return TypeHelper::int(static::get($array, $key), $default);
    }
}

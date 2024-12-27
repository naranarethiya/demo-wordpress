<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use voku\helper\ASCII;

/**
 * A helper to manipulate strings.
 */
class StringHelper
{
    /**
     * Gets the portion of a string after a given delimiter.
     *
     * @param string $string
     * @param non-empty-string $delimiter
     * @return string
     */
    public static function after(string $string, string $delimiter) : string
    {
        return array_reverse(explode($delimiter, $string, 2))[0];
    }

    /**
     * Gets the portion of a string after the last occurrence of a given delimiter.
     *
     * @param string $string
     * @param string $delimiter
     * @return string
     */
    public static function afterLast(string $string, string $delimiter) : string
    {
        if ($delimiter === '') {
            return $string;
        }

        $position = strrpos($string, $delimiter);

        if ($position === false) {
            return $string;
        }

        return substr($string, $position + strlen($delimiter));
    }

    /**
     * Gets the portion of a string before a given delimiter.
     *
     * @param string $string
     * @param string $delimiter
     * @return string
     */
    public static function before(string $string, string $delimiter) : string
    {
        return strstr($string, $delimiter, true) ?: $string;
    }

    /**
     * Gets the portion of a string before the last occurrence of a given delimiter.
     *
     * @param string $string
     * @param string $delimiter
     * @return string
     */
    public static function beforeLast(string $string, string $delimiter) : string
    {
        if ($delimiter === '') {
            return $string;
        }

        $pos = mb_strrpos($string, $delimiter);

        if ($pos === false) {
            return $string;
        }

        return static::substring($string, 0, $pos);
    }

    /**
     * Checks if a given string contains any of the other strings or characters passed.
     *
     * @param string $string
     * @param string|array<int|string, mixed>|null $values
     * @return bool
     */
    public static function contains(string $string, $values) : bool
    {
        foreach (ArrayHelper::wrap($values) as $needle) {
            if ($needle === '') {
                continue;
            }

            if (mb_strpos($string, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * urlencode string equivilent to encodeUriComponent from JS.
     *
     * @param string $string
     * @return string
     */
    public static function encodeUriComponent(string $string) : string
    {
        return strtr(rawurlencode($string), [
            '%21' => '!',
            '%2A' => '*',
            '%27' => "'",
            '%28' => '(',
            '%29' => ')',
        ]);
    }

    /**
     * Makes a string start with a specific prefix if it does not already.
     *
     * @param string $string
     * @param string $prefix
     * @return string
     */
    public static function startWith(string $string, string $prefix) : string
    {
        return $prefix.ltrim(trim($string), $prefix);
    }

    /**
     * Makes a string end with a specific suffix if it does not already.
     *
     * @param string $string
     * @param string $suffix
     * @return string
     */
    public static function endWith(string $string, string $suffix) : string
    {
        return rtrim(trim($string), $suffix).$suffix;
    }

    /**
     * Checks if the given string is valid JSON.
     *
     * @param string $subject string to modify
     * @return bool
     */
    public static function isJson(string $subject) : bool
    {
        json_decode($subject);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Replaces the first instance of $search in the given subject.
     *
     * @param string $subject string to modify
     * @param string $search value to replace
     * @param string $replace replacement value
     * @return string
     */
    public static function replaceFirst(string $subject, string $search, string $replace) : string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Sanitizes a string.
     *
     * @param string $string string to sanitize
     * @param bool $isMultiline
     * @return string sanitized string
     *
     * @deprecated Use {@see SanitizationHelper::input()} instead
     */
    public static function sanitize(string $string, bool $isMultiline = false) : string
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '', SanitizationHelper::class.'::input');

        return SanitizationHelper::input($string, $isMultiline);
    }

    /**
     * Remove slashes from the given string.
     *
     * @param string $string string to unslash
     * @return string
     */
    public static function unslash(string $string) : string
    {
        return wp_unslash($string);
    }

    /**
     * Returns the portion of the string specified by the start and length parameters.
     *
     * @param  string  $string
     * @param  int  $start
     * @param  int|null  $length
     * @return string
     */
    public static function substring(string $string, int $start, ?int $length = null) : string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Strips all non-alpha and non-numeric characters from a string, optionally replacing them with a replacement string.
     *
     * @param string $string
     * @param string $replaceWith
     * @return string
     */
    public static function stripNonAlphaNumericCharacters(string $string, string $replaceWith = '') : string
    {
        return trim((string) preg_replace('/[^\p{L}0-9'.implode('', []).']+/iu', $replaceWith, $string));
    }

    /**
     * Changes a string to snake_case.
     *
     * @NOTE Non-alpha and non-numeric characters will be removed. Umlauts and accents will be preserved.
     *
     * @param string $string
     * @param string $delimiter
     * @return string
     */
    public static function snakeCase(string $string, string $delimiter = '_') : string
    {
        // insert spaces between words
        $string = trim((string) preg_replace('#(?=\p{Lu})#u', ' ', $string));

        // replace spaces with delimiter and convert string to lowercase
        return self::lowerCase(trim(self::stripNonAlphaNumericCharacters($string, $delimiter)));
    }

    /**
     * Changes a string to kebab-case.
     *
     * @NOTE Non-alpha and non-numeric characters will be removed. Umlauts and accents will be preserved.
     *
     * @param string $string
     * @return string
     */
    public static function kebabCase(string $string) : string
    {
        return self::snakeCase($string, '-');
    }

    /**
     * Changes a string to StudlyCase.
     *
     * @NOTE Non-alpha and non-numeric characters will be removed. Umlauts and accents will be preserved.
     *
     * @param  string  $string
     * @return string
     */
    public static function studlyCase(string $string) : string
    {
        return implode(array_map([__CLASS__, 'upperCaseFirst'], explode(' ', self::stripNonAlphaNumericCharacters($string, ' '))));
    }

    /**
     * Changes a string to camelCase.
     *
     * @NOTE Non-alpha and non-numeric characters will be removed. Umlauts and accents will be preserved.
     *
     * @param string $string
     * @return string
     */
    public static function camelCase(string $string) : string
    {
        return static::lowerCaseFirst(static::studlyCase($string));
    }

    /**
     * Changes a string's first character lower-case.
     *
     * @param string $string
     * @return string
     */
    public static function lowerCaseFirst(string $string) : string
    {
        return static::lowerCase(static::substring($string, 0, 1)).static::substring($string, 1);
    }

    /**
     * Changes a string's first character upper-case.
     *
     * @param string $string
     * @return string
     */
    public static function upperCaseFirst(string $string) : string
    {
        return static::upperCase(static::substring($string, 0, 1)).static::substring($string, 1);
    }

    /**
     * Changes the given string to lower-case.
     *
     * @param string $string
     * @return string
     */
    public static function lowerCase(string $string) : string
    {
        return mb_strtolower($string, 'UTF-8');
    }

    /**
     * Changes the given string to upper-case.
     *
     * @param string $string
     * @return string
     */
    public static function upperCase(string $string) : string
    {
        return mb_strtoupper($string, 'UTF-8');
    }

    /**
     * Check if string starts with a given string or character.
     *
     * @TODO: When php 8 minimum switch to str_starts_with {JO: 2021-02-22}
     *
     * @param string $string
     * @param string $search
     * @return bool
     */
    public static function startsWith(string $string, string $search) : bool
    {
        return substr($string, 0, strlen($search)) === $search;
    }

    /**
     * Check if a string ends with a given string or character.
     *
     * @TODO When PHP 8 minimum switch to str_ends_with {agibson: 2022-06-21}
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle) : bool
    {
        if ('' === $needle || $needle === $haystack) {
            return true;
        }

        if ('' === $haystack) {
            return false;
        }

        $needleLength = strlen($needle);

        return $needleLength <= strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needleLength);
    }

    /**
     * Adds a trailing slash to a given string if one does not already exist.
     *
     * @param string $value
     * @return string
     */
    public static function trailingSlash(string $value) : string
    {
        return self::endWith($value, '/');
    }

    /**
     * Generates a RFC 4122-compliant version 4 UUID.
     *
     * This method wraps WordPress' own {@see wp_generate_uuid4()}.
     * We might consider switching to a native function, such as:
     *
     * @link https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid/15875555#15875555
     * @NOTE However, in my tests, I measured the performance of each, and the WP function appeared to be marginally faster {unfulvio 2021-05-11}
     * Likewise, if we ever need to support other versions, or pass data to this method, we can simply add optional params to it.
     *
     * @return string
     */
    public static function generateUuid4() : string
    {
        return wp_generate_uuid4();
    }

    /**
     * Ensures value passed in is scalar (int, float, string or bool), if not returns empty string.
     *
     * @param mixed $input
     * @return bool|float|int|string
     */
    public static function ensureScalar($input)
    {
        if (is_scalar($input)) {
            return $input;
        } else {
            return '';
        }
    }

    /**
     * Unserialize data recursively.
     *
     * @param mixed $value data that might be unserialized
     * @return mixed unserialized data that can be of any type
     */
    public static function maybeUnserializeRecursively($value)
    {
        $unserializedProperty = $value;

        if (is_string($value)) {
            $unserializedProperty = maybe_unserialize($value);
        }

        if (ArrayHelper::accessible($unserializedProperty)) {
            foreach (array_keys($unserializedProperty) as $key) {
                $unserializedProperty[$key] = static::maybeUnserializeRecursively($unserializedProperty[$key]);
            }
        }

        return $unserializedProperty;
    }

    /**
     * Returns an ASCII version of the string.
     *
     * Unknown characters are removed. The optional language parameter can be supplied to use language-specific transliteration.
     *
     * StringHelper::ascii('�Düsseldorf�', 'en'); // Dusseldorf
     * StringHelper::ascii('�Düsseldorf�', 'de'); // Duesseldorf
     *
     * @param string $string
     * @param string $language
     * @return string
     *
     * @phpstan-param ASCII::*_LANGUAGE_CODE $language
     *@see ASCII::to_ascii()
     */
    public static function ascii(string $string, string $language = 'en') : string
    {
        return trim(ASCII::to_ascii($string, $language));
    }

    /**
     * Transliterates a string to its closest ASCII representation.
     *
     * In comparison to `ascii()`, this method does not provide language-specific transliteration, supports more Unicode
     * characters and allows providing a replacement character for unknown Unicode characters.
     *
     * @see ASCII::to_transliterate()
     *
     * @param string $string
     * @param string|null $unknown Replacement character to use if character unknown. Pass null to keep unknown chars.
     * @param bool $strict Whether to use 'transliterator_transliterate()' from PHP-Intl
     * @return string
     */
    public static function transliterate(string $string, ?string $unknown = '?', bool $strict = false) : string
    {
        return trim(ASCII::to_transliterate($string, $unknown, $strict));
    }
}

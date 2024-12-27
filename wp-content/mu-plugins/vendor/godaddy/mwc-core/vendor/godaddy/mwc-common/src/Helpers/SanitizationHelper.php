<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

/**
 * A helper for sanitizing input.
 */
class SanitizationHelper
{
    /**
     * Sanitizes an HTML classname to ensure it only contains valid characters.
     *
     * @NOTE: $class may be nullable for backwards compatibility reasons, but we should avoid passing null, considering
     * we may change that in the future.
     *
     * @see https://developer.wordpress.org/reference/functions/sanitize_html_class/
     *
     * @param string|null $class The classname to be sanitized.
     * @param string $fallback The value to return if the sanitization ends up as an empty string.
     * @return string The sanitized value.
     */
    public static function htmlClass(?string $class, string $fallback = '') : string
    {
        /* @phpstan-ignore-next-line */
        return (string) sanitize_html_class($class, $fallback);
    }

    /**
     * Sanitizes a given string to make it no longer contain HTML tags.
     *
     * It routes to one of the other sanitization methods based on the isMultiline param: {@see textareaField} or {@see textField}.
     *
     * @NOTE: $string may be nullable for backwards compatibility reasons, but we should avoid passing null, considering
     * we may change that in the future.
     *
     * @see https://developer.wordpress.org/reference/functions/sanitize_textarea_field/
     * @see https://developer.wordpress.org/reference/functions/sanitize_text_field/
     *
     * @param string|null $string String to sanitize
     * @param bool $isMultiline Whether the string is multiline or not.
     * @return string Sanitized string.
     */
    public static function input(?string $string, bool $isMultiline = false) : string
    {
        /* @phpstan-ignore-next-line */
        return $isMultiline ? static::textareaField($string) : static::textField($string);
    }

    /**
     * Sanitizes a string into a slug, which can be used in URLs or HTML attributes.
     *
     * @NOTE: $string may be nullable for backwards compatibility reasons, but we should avoid passing null, considering
     * we may change that in the future.
     *
     * @see https://developer.wordpress.org/reference/functions/sanitize_title/
     *
     * @param string|null $string The string to be sanitized.
     * @param string $fallbackTitle A title to use if $title is empty.
     * @param string $context The operation for which the string is sanitized.
     * @return string The sanitized string.
     */
    public static function slug(?string $string, string $fallbackTitle = '', string $context = 'save') : string
    {
        /* @phpstan-ignore-next-line */
        return (string) sanitize_title($string, $fallbackTitle, $context);
    }

    /**
     * Sanitizes a multiline string from a textarea field.
     *
     * @NOTE: $string may be nullable for backwards compatibility reasons, but we should avoid passing null, considering
     * we may change that in the future.
     *
     * @see https://developer.wordpress.org/reference/functions/sanitize_textarea_field/
     *
     * It does what sanitize_textarea_field does:
     * - Checks for invalid UTF-8,
     * - Converts single < characters to entities
     * - Strips all tags
     * - Strips octets
     *
     * @param string|null $string String to sanitize.
     * @return string Sanitized string.
     */
    public static function textareaField(?string $string) : string
    {
        /* @phpstan-ignore-next-line */
        return (string) sanitize_textarea_field($string);
    }

    /**
     * Sanitizes a string from a text field.
     *
     * @NOTE: $string may be nullable for backwards compatibility reasons, but we should avoid passing null, considering
     * we may change that in the future.
     *
     * @see https://developer.wordpress.org/reference/functions/sanitize_text_field/
     *
     * It does what sanitize_text_field does:
     * - Checks for invalid UTF-8,
     * - Converts single < characters to entities
     * - Strips all tags
     * - Removes line breaks, tabs, and extra whitespace
     * - Strips octets
     *
     * @param string|null $string String to sanitize.
     * @return string Sanitized string.
     */
    public static function textField(?string $string) : string
    {
        /* @phpstan-ignore-next-line */
        return (string) sanitize_text_field($string);
    }

    /**
     * Sanitizes a username, stripping out unsafe characters.
     *
     * @NOTE: $username may be nullable for backwards compatibility reasons, but we should avoid passing null, considering
     * we may change that in the future.
     *
     * @see https://developer.wordpress.org/reference/functions/sanitize_user/
     *
     * @param string|null $username The username to be sanitized.
     * @param bool $strict If set, limits $username to specific characters.
     * @return string The sanitized username, after passing through filters.
     */
    public static function username(?string $username, bool $strict = true) : string
    {
        /* @phpstan-ignore-next-line */
        return (string) sanitize_user($username, $strict);
    }
}

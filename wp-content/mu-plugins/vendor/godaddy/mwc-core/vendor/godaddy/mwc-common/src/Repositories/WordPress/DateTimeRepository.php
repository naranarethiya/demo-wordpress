<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * Repository handler for WordPress date-time functions and methods.
 */
class DateTimeRepository
{
    /**
     * Gets the local timezone string for the site.
     *
     * @TODO: when WordPress 5.3+ is required this method can be simplified to simply return {@see wp_timezone_string()}.
     *
     * @return string
     */
    public static function getLocalTimeZoneString() : string
    {
        // function available from WordPress 5.3+
        if (function_exists('wp_timezone_string')) {
            return wp_timezone_string();
        }

        // fallback to WooCommerce function
        if (function_exists('wc_timezone_string')) {
            return wc_timezone_string();
        }

        // if WooCommerce is not available use the WordPress option
        if ($timezoneString = TypeHelper::stringOrNull(get_option('timezone_string'))) {
            return $timezoneString;
        }

        // there's a chance the site may be using a UTC offset instead of a timezone string
        return static::getLocalTimeZoneOffset();
    }

    /**
     * Gets the local timezone UTC offset.
     *
     * This is an @internal method used by {@see DateTimeRepository::getLocalTimeZoneString()} as fallback when {@see wp_timezone_string()} is not available.
     * The code below is taken from {@see wp_timezone_string()}.
     *
     * @return string
     */
    protected static function getLocalTimeZoneOffset() : string
    {
        $offset = (float) TypeHelper::string(get_option('gmt_offset', 0), '0');
        $hours = abs((int) $offset);
        $minutes = abs(($offset - (int) $offset) * 60);
        $sign = ($offset < 0) ? '-' : '+';

        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
    }

    /**
     * Gets WordPress local timezone for the site.
     *
     * @return DateTimeZone
     * @throws Exception
     */
    public static function getLocalDateTimeZone() : DateTimeZone
    {
        return function_exists('wp_timezone')
            ? wp_timezone()
            : new DateTimeZone(static::getLocalTimeZoneString());
    }

    /**
     * Gets the date format from WordPress settings.
     *
     * @return string
     */
    public static function getDateFormat() : string
    {
        if (WooCommerceRepository::isWooCommerceActive()) {
            return (string) wc_date_format();
        }

        $defaultFormat = 'F j, Y';
        $dateFormat = get_option('date_format', $defaultFormat);

        if (empty($dateFormat) || ! is_string($dateFormat)) {
            return $defaultFormat;
        }

        return $dateFormat;
    }

    /**
     * Gets the time format from WordPress settings.
     *
     * @return string
     */
    public static function getTimeFormat() : string
    {
        if (WooCommerceRepository::isWooCommerceActive()) {
            return (string) wc_time_format();
        }

        $defaultFormat = 'g:i a';
        $timeFormat = get_option('time_format', $defaultFormat);

        if (empty($timeFormat) || ! is_string($timeFormat)) {
            return $defaultFormat;
        }

        return $timeFormat;
    }

    /**
     * Gets a localized date.
     *
     * @param string $format the PHP format used to display the date
     * @param int|false $timestamp optional timestamp with offset
     * @param bool $utc whether date is assumed UTC (only used if timestamp offset not provided)
     * @return string
     */
    public static function getLocalizedDate(string $format, $timestamp = false, bool $utc = false) : string
    {
        return TypeHelper::string(date_i18n($format, $timestamp, $utc), '');
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Trait for converting a datetime value within a Commerce response payload to formatted date string.
 */
trait CanConvertDateTimeFromTimestampTrait
{
    /**
     * Converts a datetime value from a Commerce response to a formatted date string.
     *
     * @param array<string, mixed> $responseData
     * @param string $key
     * @return string|null
     */
    protected function convertDateTimeFromTimestamp(array $responseData, string $key) : ?string
    {
        $dateTime = TypeHelper::string(ArrayHelper::get($responseData, $key), '');

        if (empty($dateTime)) {
            return null;
        }

        try {
            return $this->formatDateTimeString($dateTime);
        } catch (Exception $e) {
            return $this->handleDateTimeFormattingError($dateTime, $e);
        }
    }

    /**
     * Formats a datetime string.
     *
     * @param string $dateTime
     * @return string
     * @throws Exception
     */
    protected function formatDateTimeString(string $dateTime) : string
    {
        return (new DateTime($dateTime))->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Handles an exception that occurs when attempting to re-format the datetime string.
     *
     * This method specifically attempts to account for a DateTime parsing bug that appears in PHP 7.4.
     * @link https://bugs.php.net/bug.php?id=51987
     *
     * @param string $dateTimeString
     * @param Exception $exception
     * @return string|null
     */
    protected function handleDateTimeFormattingError(string $dateTimeString, Exception $exception) : ?string
    {
        /*
         * We're looking for an exception message like this:
         *
         * Fatal error: Uncaught Exception: DateTime::__construct(): Failed to parse time string (2024-05-03T14:22:49.124628053Z) at position 0 (2): The timezone could not be found in the database in Standard input code:4
         */
        if (! StringHelper::contains($exception->getMessage(), 'The timezone could not be found')) {
            return null;
        }

        // if we chop off the decimal then parsing will work
        if (! $newDateTimeString = StringHelper::before($dateTimeString, '.')) {
            return null;
        }

        try {
            return $this->formatDateTimeString($newDateTimeString);
        } catch(Exception $e) {
            return null;
        }
    }
}

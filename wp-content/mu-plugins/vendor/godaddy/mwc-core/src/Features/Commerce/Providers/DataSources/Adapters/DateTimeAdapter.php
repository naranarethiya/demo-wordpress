<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;

class DateTimeAdapter implements DataObjectAdapterContract
{
    /** @var non-empty-string */
    protected const DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Converts a Commerce's datetime string into a datetime instance.
     *
     * @param string|null $source
     * @return DateTime|null
     */
    public function convertFromSource($source) : ?DateTime
    {
        if (! $source) {
            return null;
        }

        try {
            return new DateTime($source, new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Converts a datetime instance into a Commerce's datetime string.
     *
     * @param DateTimeInterface|null $target
     * @return non-empty-string|null
     */
    public function convertToSource($target) : ?string
    {
        if (! $target instanceof DateTimeInterface) {
            return null;
        }

        try {
            $dateTimeToFormat = $this->createDateTimeImmutableFromInterface($target);
        } catch (Exception $e) {
            return null;
        }

        return $this->format($dateTimeToFormat);
    }

    /**
     * Create a DateTimeImmutable from a DateTimeInterface.
     * Conversion has resolution only in seconds: milliseconds are stripped due to use of int timestamp.
     *
     * @note A shim until we upgrade to PHP 8, when DateTimeImmutable::createFromInterface() is available.
     *
     * @param DateTimeInterface $dateTime
     *
     * @return DateTimeImmutable
     * @throws Exception
     */
    protected function createDateTimeImmutableFromInterface(DateTimeInterface $dateTime) : DateTimeImmutable
    {
        return (new DateTimeImmutable('@'.$dateTime->getTimestamp()))->setTimezone($dateTime->getTimezone());
    }

    /**
     * Formats the given datetime instance using the configured format.
     *
     * @param DateTimeImmutable $dateTime
     * @return non-empty-string
     */
    protected function format(DateTimeImmutable $dateTime) : string
    {
        /** @var non-empty-string $formatted */
        $formatted = $dateTime->setTimezone(new DateTimeZone('UTC'))->format(static::DATETIME_FORMAT);

        return $formatted;
    }

    /**
     * Converts a datetime instance into a Commerce's datetime string.
     *
     * Returns the string representation of the current time if the given instance is null.
     *
     * @param DateTimeInterface|null $target
     * @return non-empty-string
     */
    public function convertToSourceOrNow($target) : string
    {
        return $this->convertToSource($target) ?? $this->format(new DateTimeImmutable());
    }
}

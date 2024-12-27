<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use DateTime;

/**
 * A contract for email notifications that can be delayed.
 */
interface DelayableEmailNotificationContract
{
    /**
     * Gets the amount of units that a notification should be delayed by.
     *
     * @return int
     */
    public function getDelayValue() : int;

    /**
     * Gets the units relating to the value above that a notification should be delayed by.
     *
     * @return string
     */
    public function getDelayUnit() : string;

    /**
     * Sets the amount of units that a notification should be delayed by.
     *
     * @return self
     */
    public function setDelayValue(int $value);

    /**
     * Sets the units relating to the value above that a notification should be delayed by.
     *
     * @return self
     */
    public function setDelayUnit(string $value);

    /**
     * Determines the DateTime the email should be scheduled for.
     *
     * @return DateTime|null
     */
    public function sendAt() : ?DateTime;
}

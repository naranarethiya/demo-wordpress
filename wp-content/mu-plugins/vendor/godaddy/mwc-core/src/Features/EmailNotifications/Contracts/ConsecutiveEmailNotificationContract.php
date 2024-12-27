<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

interface ConsecutiveEmailNotificationContract extends EmailNotificationContract
{
    /**
     * Gets the position of the email notification relative to others in the series.
     *
     * @return int
     */
    public function getPosition() : int;
}

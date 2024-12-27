<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

/**
 * Contract for email notifications which should perform a check before they are sent.
 */
interface ConditionalEmailNotificationContract
{
    /**
     * Determines whether the email should be sent.
     *
     * @return bool
     */
    public function shouldSend() : bool;
}

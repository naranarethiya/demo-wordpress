<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

interface EmailNotificationCampaignStrategyContract
{
    /**
     * Gets the first email notification of the campaign.
     *
     * @return ConsecutiveEmailNotificationContract|null
     */
    public function getFirstEmailNotification() : ?ConsecutiveEmailNotificationContract;

    /**
     * Gets the email notification that comes immediately after the given email notification.
     *
     * @param ConsecutiveEmailNotificationContract $emailNotification
     * @return ConsecutiveEmailNotificationContract|null
     */
    public function getNextEmailNotificationAfter(ConsecutiveEmailNotificationContract $emailNotification) : ?ConsecutiveEmailNotificationContract;

    /**
     * Determines whether we should schedule the first email of the campaign.
     *
     * @return bool
     */
    public function shouldStartCampaign() : bool;
}

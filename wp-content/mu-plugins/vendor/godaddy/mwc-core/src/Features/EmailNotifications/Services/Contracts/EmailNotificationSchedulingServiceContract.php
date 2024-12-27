<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryEmailNotificationScheduleFailedException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConsecutiveEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationCampaignStrategyContract;

interface EmailNotificationSchedulingServiceContract
{
    /**
     * Sets the email campaign strategy for this service.
     *
     * @param EmailNotificationCampaignStrategyContract $value
     * @return $this
     */
    public function setStrategy(EmailNotificationCampaignStrategyContract $value);

    /**
     * Gets the email campaign strategy for this service.
     *
     * @return EmailNotificationCampaignStrategyContract|null
     */
    public function getStrategy() : ?EmailNotificationCampaignStrategyContract;

    /**
     * Tries to schedule the first email notification of the campaign.
     *
     * @return void
     * @throws CartRecoveryEmailNotificationScheduleFailedException
     * @throws CartRecoveryException
     */
    public function tryToScheduleFirstEmail() : void;

    /**
     * Tries to schedule the email notification that goes after the given email notification.
     *
     * @param ConsecutiveEmailNotificationContract $emailNotification
     * @return void
     * @throws CartRecoveryEmailNotificationScheduleFailedException
     * @throws CartRecoveryException
     */
    public function tryToScheduleNextEmailAfter(ConsecutiveEmailNotificationContract $emailNotification) : void;
}

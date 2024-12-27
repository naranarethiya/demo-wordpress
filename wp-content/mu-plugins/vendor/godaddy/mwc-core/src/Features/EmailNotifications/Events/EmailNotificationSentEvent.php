<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;

/**
 * @method static static getNewInstance(EmailNotificationContract $emailNotification)
 */
class EmailNotificationSentEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var EmailNotificationContract */
    protected $emailNotification;

    public function __construct(EmailNotificationContract $emailNotification)
    {
        $this->emailNotification = $emailNotification;
    }

    /**
     * Gets the email notification associated with this event.
     *
     * @return EmailNotificationContract
     */
    public function getEmailNotification() : EmailNotificationContract
    {
        return $this->emailNotification;
    }
}

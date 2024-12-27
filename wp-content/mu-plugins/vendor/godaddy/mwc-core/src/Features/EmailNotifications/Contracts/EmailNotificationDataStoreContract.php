<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailContentNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use InvalidArgumentException;

/**
 * The Email Notification Data Store contract.
 */
interface EmailNotificationDataStoreContract
{
    /**
     * Gets an email notification with the given ID.
     *
     * @param string $id
     * @return EmailNotificationContract
     * @throws EmailNotificationNotFoundException
     */
    public function read(string $id) : EmailNotificationContract;

    /**
     * Saves the given email notification.
     *
     * @param EmailNotificationContract $notification
     * @return EmailNotificationContract
     */
    public function save(EmailNotificationContract $notification) : EmailNotificationContract;

    /**
     * Returns an array of all available EmailNotificationContract objects.
     *
     * @return EmailNotificationContract[]
     * @throws EmailContentNotFoundException
     * @throws EmailNotificationNotFoundException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     * @throws InvalidArgumentException
     */
    public function all() : array;
}

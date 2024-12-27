<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Email\Emails;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseTableDoesNotExistException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryEmailNotificationScheduleFailedException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\CartRecoveryEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConsecutiveEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationCampaignStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailBuilder;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Services\Contracts\EmailNotificationSchedulingServiceContract;

class CartRecoveryEmailsNotificationSchedulingService implements EmailNotificationSchedulingServiceContract
{
    use CanGetNewInstanceTrait;

    /** @var ?EmailNotificationCampaignStrategyContract */
    protected $strategy = null;

    /**
     * {@inheritDoc}
     */
    public function setStrategy(EmailNotificationCampaignStrategyContract $value)
    {
        $this->strategy = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getStrategy() : ?EmailNotificationCampaignStrategyContract
    {
        return $this->strategy;
    }

    /**
     * {@inheritDoc}
     */
    public function tryToScheduleFirstEmail() : void
    {
        if (! $strategy = $this->getStrategy()) {
            throw new CartRecoveryException('Missing campaign strategy.');
        }

        if (! $emailNotification = $strategy->getFirstEmailNotification()) {
            throw new CartRecoveryException('Could not find the first email notification for the campaign.');
        }

        if (! $strategy->shouldStartCampaign()) {
            throw new CartRecoveryException('Could not start a new campaign at this time.');
        }

        $this->scheduleEmail($emailNotification);
    }

    /**
     * Schedules the given email notification for sending.
     *
     * @param EmailNotificationContract $emailNotification
     * @throws CartRecoveryEmailNotificationScheduleFailedException
     * @throws CartRecoveryException
     */
    protected function scheduleEmail(EmailNotificationContract $emailNotification) : void
    {
        try {
            $this->tryToScheduleCartRecoveryEmailNotification($emailNotification);
        } catch (WordPressDatabaseTableDoesNotExistException $exception) {
            // do not report to Sentry to reduce noise (we are already reporting when the table creation fails)
            throw new CartRecoveryException($exception->getMessage(), $exception);
        } catch (Exception $exception) {
            throw new CartRecoveryEmailNotificationScheduleFailedException($exception->getMessage(), $exception);
        }
    }

    /**
     * Schedules the given cart recovery email notification for sending.
     *
     * @param EmailNotificationContract $emailNotification
     * @throws WordPressDatabaseTableDoesNotExistException
     * @throws CartRecoveryException
     * @throws Exception
     */
    protected function tryToScheduleCartRecoveryEmailNotification(EmailNotificationContract $emailNotification) : void
    {
        if (! $emailNotification instanceof CartRecoveryEmailNotification) {
            throw new CartRecoveryException('The email notification is not a cart recovery email notification.');
        }

        if (! $checkout = $emailNotification->getCheckout()) {
            throw new CartRecoveryException('The email notification has no Checkout instance associated.');
        }

        Emails::send($this->getRenderableEmail($emailNotification, $checkout));
    }

    /**
     * Builds a {@see RenderableEmailContract} instance using the given email notification and checkout instances.
     *
     * @param CartRecoveryEmailNotification $emailNotification
     * @param Checkout $checkout
     * @return RenderableEmailContract
     * @throws Exception
     */
    protected function getRenderableEmail(CartRecoveryEmailNotification $emailNotification, Checkout $checkout) : RenderableEmailContract
    {
        return $this->getEmailBuilder($emailNotification)
            ->setRecipients([$checkout->getEmailAddress()])
            ->build();
    }

    /**
     * Creates a new instance of {@see EmailBuilder}.
     *
     * @param EmailNotificationContract $emailNotification
     * @return EmailBuilder
     */
    protected function getEmailBuilder(EmailNotificationContract $emailNotification) : EmailBuilder
    {
        return new EmailBuilder($emailNotification);
    }

    /**
     * {@inheritDoc}
     */
    public function tryToScheduleNextEmailAfter(ConsecutiveEmailNotificationContract $emailNotification) : void
    {
        if (! $this->strategy) {
            throw new CartRecoveryException('Missing campaign strategy.');
        }

        // Uses the strategy to get the next email notification in the campaign. If the method can find one, it schedules that email notification.
        if (! $nextEmailNotification = $this->strategy->getNextEmailNotificationAfter($emailNotification)) {
            throw new CartRecoveryException('Could not find the next email notification for the campaign.');
        }

        $this->scheduleEmail($nextEmailNotification);
    }
}

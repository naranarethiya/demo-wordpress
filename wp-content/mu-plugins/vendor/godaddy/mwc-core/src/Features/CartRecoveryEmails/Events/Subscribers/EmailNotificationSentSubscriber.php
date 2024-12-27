<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmailNotificationCampaignStrategy;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryEmailNotificationScheduleFailedException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\CartRecoveryEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Services\CartRecoveryEmailsNotificationSchedulingService;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events\EmailNotificationSentEvent;

class EmailNotificationSentSubscriber implements SubscriberContract
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if ($event instanceof EmailNotificationSentEvent) {
            $this->onEmailNotificationSent($event);
        }
    }

    /**
     * Schedules the next email notification in the campaign if given email is cart recovery email.
     *
     * @param EmailNotificationSentEvent $event
     * @return void
     */
    protected function onEmailNotificationSent(EmailNotificationSentEvent $event) : void
    {
        $emailNotification = $event->getEmailNotification();
        if ($emailNotification instanceof CartRecoveryEmailNotification) {
            $this->onCartRecoveryEmailNotificationSent($emailNotification);
        }
    }

    /**
     * Tries to schedule the next email in the campaign.
     *
     * @param CartRecoveryEmailNotification $emailNotification
     * @return void
     */
    protected function onCartRecoveryEmailNotificationSent(CartRecoveryEmailNotification $emailNotification) : void
    {
        $checkout = $emailNotification->getCheckout();

        if (! $checkout) {
            return;
        }

        $service = CartRecoveryEmailsNotificationSchedulingService::getNewInstance()
            ->setStrategy(CartRecoveryEmailNotificationCampaignStrategy::fromCheckout($checkout));

        try {
            $service->tryToScheduleNextEmailAfter($emailNotification);
        } catch (CartRecoveryEmailNotificationScheduleFailedException|CartRecoveryException $exception) {
            // do nothing: SentryException subclasses will be reported to Sentry automatically
        }
    }
}

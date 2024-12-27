<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Subscribers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmailNotificationCampaignStrategy;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmails;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryEmailNotificationScheduleFailedException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Services\CartRecoveryEmailsNotificationSchedulingService;

/**
 * Subscriber that listens to {@see Checkout} model events and possibly schedules cart recovery emails.
 *
 * @see Checkout
 * @see ModelEvent
 */
class CheckoutSubscriber implements SubscriberContract
{
    /** @var string transient key placeholder */
    public const TRANSIENT_PROCESSING_CHECKOUT_EVENT = 'mwc_cart_recovery_processing_checkout_event_checkout_id_';

    /**
     * Handles the event.
     *
     * @param EventContract|ModelEvent $event
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        /** @var Checkout $checkout */
        if (! $checkout = $event->getModel()) {
            return;
        }

        // set transient to prevent overlapping
        set_transient(static::TRANSIENT_PROCESSING_CHECKOUT_EVENT.$checkout->getId(), 1, 5);

        $this->processCheckoutEvent($checkout);

        delete_transient(static::TRANSIENT_PROCESSING_CHECKOUT_EVENT.$checkout->getId());
    }

    /**
     * Determines whether the given event should be handled.
     *
     * @param EventContract $event event object
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return CartRecoveryEmails::shouldLoad()
            && $event instanceof ModelEvent
            && 'checkout' === $event->getResource()
            && ArrayHelper::contains(['create', 'update'], $event->getAction())
            && $event->getModel() instanceof Checkout
            && empty(get_transient(static::TRANSIENT_PROCESSING_CHECKOUT_EVENT.$event->getModel()->getId()));
    }

    /**
     * Tries to schedule the first email in the Cart Recovery email notification campaign.
     *
     * @param Checkout $checkout
     * @return void
     */
    protected function processCheckoutEvent(Checkout $checkout) : void
    {
        $service = CartRecoveryEmailsNotificationSchedulingService::getNewInstance()
            ->setStrategy(CartRecoveryEmailNotificationCampaignStrategy::fromCheckout($checkout));

        try {
            $service->tryToScheduleFirstEmail();
        } catch (CartRecoveryEmailNotificationScheduleFailedException|CartRecoveryException $exception) {
            return;
        }

        $checkout->setEmailScheduledAt(new DateTime());

        try {
            $checkout->save();
        } catch (Exception $exception) {
            // no need to throw SentryException instances as they are reported to Sentry automatically
            new SentryException($exception->getMessage(), $exception);
        }
    }
}

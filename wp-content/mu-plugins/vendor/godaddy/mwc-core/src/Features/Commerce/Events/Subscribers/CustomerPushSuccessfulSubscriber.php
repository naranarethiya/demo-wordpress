<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CommerceCustomerPush;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushSuccessfulEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Jobs\AbstractPushJob;

class CustomerPushSuccessfulSubscriber implements SubscriberContract
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        /** @var CustomerPushSuccessfulEvent $event */
        if (! $this->isValidEvent($event)) {
            return;
        }

        if (! CommerceCustomerPush::shouldLoad()) {
            return;
        }

        /** @var AbstractPushJob $job */
        $job = $event->getJob();

        $job->delete();
    }

    /**
     * Returns true if this event is valid.
     *
     * @param EventContract $event
     *
     * @return bool
     */
    protected function isValidEvent(EventContract $event) : bool
    {
        return $event instanceof CustomerPushSuccessfulEvent && $event->getJob() instanceof AbstractPushJob;
    }
}

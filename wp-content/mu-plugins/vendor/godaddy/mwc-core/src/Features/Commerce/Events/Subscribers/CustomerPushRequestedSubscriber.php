<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CommerceCustomerPush;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushRequestedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Jobs\CustomerPushJob;

class CustomerPushRequestedSubscriber implements SubscriberContract
{
    /**
     * @param CustomerPushRequestedEvent $event
     */
    public function handle(EventContract $event) : void
    {
        if (! CommerceCustomerPush::shouldLoad()) {
            return;
        }

        foreach ($event->getCustomers() as $customer) {
            if ($customer->getId()) {
                $this->createJob($customer->getId());
            }
        }
    }

    /**
     * Creates a new job to push the customer with the given ID to the commerce platform.
     *
     * @param int $customerId
     */
    protected function createJob(int $customerId) : void
    {
        try {
            CustomerPushJob::create([
                'objectIds' => [$customerId],
            ]);
        } catch (Exception $e) {
            new SentryException($e->getMessage(), $e);
        }
    }
}

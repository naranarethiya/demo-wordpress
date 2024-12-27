<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CommerceCustomerPush;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushRequestedEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

class CustomerSubscriber implements SubscriberContract
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if (! $event instanceof ModelEvent) {
            return;
        }

        if ('customer' !== $event->getResource() || 'create' !== $event->getAction()) {
            return;
        }

        if (! CommerceCustomerPush::shouldLoad()) {
            return;
        }

        $model = $event->getModel();

        if (! $model instanceof Customer) {
            return;
        }

        $this->broadcastCustomerPushRequest($model);
    }

    /**
     * Broadcasts a {@see CustomerPushRequestedEvent} event for the given customer.
     *
     * @param Customer $customer
     *
     * @return void
     */
    protected function broadcastCustomerPushRequest(Customer $customer) : void
    {
        Events::broadcast($this->buildCustomerPushEvent($customer));
    }

    /**
     * Builds customer push event for the given customer instance.
     *
     * @param Customer $customer
     *
     * @return CustomerPushRequestedEvent
     */
    protected function buildCustomerPushEvent(Customer $customer) : CustomerPushRequestedEvent
    {
        return (new CustomerPushRequestedEvent())->setCustomers([$customer]);
    }
}

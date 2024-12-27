<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Subscribers;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;

abstract class AbstractFailHandlerSubscriber implements SubscriberContract
{
    /**
     * Gets the notice for the subscriber.
     *
     * @param string|null $failReason
     * @return Notice
     */
    abstract public function getNotice(string $failReason = null) : Notice;

    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     */
    public function handle(EventContract $event) : void
    {
        Notices::enqueueAdminNotice($this->getNotice());
    }
}

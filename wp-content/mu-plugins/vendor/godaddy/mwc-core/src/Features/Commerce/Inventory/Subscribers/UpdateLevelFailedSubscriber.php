<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Subscribers;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\UpdateLevelFailedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices\Flags\ProductInventoryUpdateFailedNoticeFlag;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices\ProductInventoryUpdateFailedNotice;

class UpdateLevelFailedSubscriber extends AbstractFailHandlerSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getNotice(string $failReason = null) : Notice
    {
        return ProductInventoryUpdateFailedNotice::getNewInstance($failReason);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if ($event instanceof UpdateLevelFailedEvent) {
            ProductInventoryUpdateFailedNoticeFlag::getNewInstance()->turnOn($event->failReason);
        }
    }
}

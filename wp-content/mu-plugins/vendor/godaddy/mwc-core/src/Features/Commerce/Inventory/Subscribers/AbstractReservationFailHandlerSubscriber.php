<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Subscribers;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\AbstractReservationFailedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices\OrderInventoryUpsertFailedNotice;
use WC_Order;

abstract class AbstractReservationFailHandlerSubscriber extends AbstractFailHandlerSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getNotice(string $failReason = null) : Notice
    {
        return OrderInventoryUpsertFailedNotice::getNewInstance();
    }

    /**
     * Gets the WC_Order object.
     *
     * @param EventContract $event
     * @return WC_Order|null
     */
    protected function getOrder(EventContract $event) : ?WC_Order
    {
        /* @var AbstractReservationFailedEvent $event */
        return OrdersRepository::get((int) $event->order->getId()); /* @phpstan-ignore-line */
    }
}

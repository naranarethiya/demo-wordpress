<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\CreateReservationFailedEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Exceptions\MissingOrderException;
use WC_Order;

class CreateReservationFailedSubscriber extends AbstractReservationFailHandlerSubscriber
{
    /**
     * Handles the event, adding an order note.
     *
     * @param EventContract $event
     * @throws MissingOrderException
     */
    public function handle(EventContract $event) : void
    {
        /** @var CreateReservationFailedEvent $event */
        if (! $event instanceof CreateReservationFailedEvent) {
            return;
        }

        if (! ($order = $this->getOrder($event)) instanceof WC_Order) {
            throw new MissingOrderException('Order not found');
        }

        // @TODO Implement in MWC-11519 {ssmith1 - 2023-04-10}
        $order->add_order_note('Placeholder order note');

        parent::handle($event);
    }
}

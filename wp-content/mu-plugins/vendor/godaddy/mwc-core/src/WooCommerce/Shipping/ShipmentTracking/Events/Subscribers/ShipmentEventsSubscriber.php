<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Shipping;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Traits\CanCheckShipmentStatusTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentCreatedEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentUpdatedEvent;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\CancelledPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\CreatedPackageStatus;

class ShipmentEventsSubscriber implements SubscriberContract
{
    use CanCheckShipmentStatusTrait;

    /**
     * Handles the AbstractShipmentEvent.
     *
     * @since 2.10.0
     *
     * @param EventContract $event
     */
    public function handle(EventContract $event)
    {
        if ($this->shouldFireTrackingInformationAddedAction($event)) {
            $this->fireTrackingInformationAddedAction($event->getOrderFulfillment()->getOrder()->getId());
        }
    }

    /**
     * Determines whether the mwc_shipment_tracking_information_added action should be fired.
     *
     * @since 2.10.0
     *
     * @param EventContract $event event object
     *
     * @return bool
     */
    protected function shouldFireTrackingInformationAddedAction(EventContract $event) : bool
    {
        if (! ($event instanceof ShipmentCreatedEvent || $event instanceof ShipmentUpdatedEvent)) {
            return false;
        }

        return $this->shouldFireTrackingInformationAddedActionForShipment($event->getShipment());
    }

    /**
     * Determines whether the mwc_shipment_tracking_information_added action should be fired for the given shipment.
     *
     * @param ShipmentContract $shipment
     * @return bool
     */
    protected function shouldFireTrackingInformationAddedActionForShipment(ShipmentContract $shipment) : bool
    {
        // if the Shipping Labels integration is disabled we should fire the action for all shipments
        if (! Shipping::shouldLoad()) {
            return true;
        }

        if ($this->shipmentHasPackagesWithStatus($shipment, CreatedPackageStatus::class, CancelledPackageStatus::class)) {
            return false;
        }

        return true;
    }

    /**
     * Fires the mwc_shipment_tracking_information_added action.
     *
     * @since 2.10.0
     *
     * @param int $orderId
     */
    protected function fireTrackingInformationAddedAction(int $orderId)
    {
        /*
         * Fires when shipment tracking information is added to an order.
         *
         * @param int $orderId
         */
        do_action('mwc_shipment_tracking_information_added', $orderId);
    }
}

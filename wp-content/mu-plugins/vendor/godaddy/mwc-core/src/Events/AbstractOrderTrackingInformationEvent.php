<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Abstract order tracking information event class.
 */
abstract class AbstractOrderTrackingInformationEvent extends AbstractOrderEvent
{
    /** @var array the tracking items */
    protected $trackingItems = [];

    /**
     * Event constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->resource = 'order_tracking_information';
    }

    /**
     * Sets the tracking items for this event.
     *
     * @param array $trackingItems
     * @return self
     */
    public function setTrackingItems(array $trackingItems = []) : self
    {
        $this->trackingItems = $trackingItems;

        return $this;
    }

    /**
     * Builds the initial data for the event.
     *
     * @return array
     */
    protected function buildInitialData() : array
    {
        return $this->order ? [
            'order'         => $this->getOrderData($this->order),
            'trackingItems' => $this->trackingItems,
        ] : [];
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;
use GoDaddy\WordPress\MWC\Shipping\Fulfillment as ShippingFulfillment;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;

/**
 * Fulfillment handler.
 *
 * TODO: update mwc-core and mwc-dashboard to inject instances of {@see Fulfillment} instead of
 *       calling Fulfillment::getInstance() -- https://jira.godaddy.com/browse/MWC-7976 {wvega 2022-09-02}
 */
class Fulfillment extends ShippingFulfillment
{
    use IsSingletonTrait;

    /**
     * Updates the given order fulfillment shipments statuses.
     *
     * @param OrderFulfillment $fulfillment
     * @throws Exception
     */
    public function update(OrderFulfillment $fulfillment) : void
    {
        parent::update($fulfillment);

        $this->getOrderFulfillmentDataStore()->save($fulfillment);
    }

    /**
     * Gets an instance of OrderFulfillmentDataStore.
     *
     * @return OrderFulfillmentDataStore
     */
    protected function getOrderFulfillmentDataStore() : OrderFulfillmentDataStore
    {
        return new OrderFulfillmentDataStore();
    }
}

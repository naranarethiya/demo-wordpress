<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories\OrdersRepository;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Events\AbstractShipmentEvent as ShippingAbstractShipmentEvent;
use WC_Product;

/**
 * Shipment event abstract class.
 */
abstract class AbstractShipmentEvent extends ShippingAbstractShipmentEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /**
     * Builds the initial data for the current event.
     *
     * @return array
     * @throws BaseException
     */
    protected function buildInitialData() : array
    {
        $providerName = $this->getShipment()->getProviderName();
        $packages = $this->getShipment()->getPackages();

        return [
            'resource' => $this->getShipment()->toArray(),
            'order'    => [
                'id'                     => $this->getOrderFulfillment()->getOrder()->getId(),
                'marketplacesInternalId' => $this->getMarketplacesInternalId(),
            ],
            'shipment' => [
                'id'              => $this->getShipment()->getId(),
                'isKnownProvider' => 'other' !== $providerName,
                'providerName'    => $providerName,
                'providerLabel'   => $this->getShipment()->getProviderLabel(),
                'trackingNumber'  => ! empty($packages) ? current($packages)->getTrackingNumber() : null,
                'trackingUrl'     => ! empty($packages) ? current($packages)->getTrackingUrl() : '',
                'itemsCount'      => ! empty($packages) ? count(current($packages)->getItems()) : 0,
                'items'           => ! empty($packages) ? $this->getShipmentItems(array_values($packages)) : [],
            ],
        ];
    }

    /**
     * Gets the marketplaces internal id, if available.
     *
     * @return string|null
     */
    protected function getMarketplacesInternalId() : ?string
    {
        $order = $this->getCoreOrder();

        return $order ? $order->getMarketplacesInternalOrderNumber() : null;
    }

    /**
     * Gets a Core Order object. A common order object will be re-adapted if necessary.
     *
     * @return Order|null
     */
    protected function getCoreOrder() : ?Order
    {
        $orderFromFulfillment = $this->getOrderFulfillment()->getOrder();

        if ($orderFromFulfillment->getId() && $wcOrder = OrdersRepository::get($orderFromFulfillment->getId())) {
            $coreOrderAdapter = OrderAdapter::getNewInstance($wcOrder);

            try {
                return $coreOrderAdapter->convertFromSource();
            } catch (AdapterException $e) {
            }
        }

        return null;
    }

    /**
     * Gets an array of items included in this shipment.
     *
     * @param PackageContract[] $packages
     * @return array<array<int, array<string, float|string|null>>>
     * @throws BaseException
     */
    protected function getShipmentItems(array $packages) : array
    {
        $items = [];

        foreach ($packages as $package) {
            $items = ArrayHelper::combine($items, array_map(function (LineItem $item) {
                $wcProduct = $item->getProduct();
                if ($wcProduct instanceof WC_Product) {
                    $sku = $wcProduct->get_sku() ?: null;
                }

                return [
                    'sku'      => $sku ?? null,
                    'quantity' => $item->getQuantity(),
                ];
            }, array_values($package->getItems())));
        }

        return $items;
    }
}

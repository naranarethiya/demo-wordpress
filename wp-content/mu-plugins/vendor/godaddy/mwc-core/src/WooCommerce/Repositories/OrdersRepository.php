<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository as CommonOrdersRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

/**
 * Orders repository to handle Core specific logic.
 */
class OrdersRepository extends CommonOrdersRepository
{
    /**
     * Gets a WooCommerce order object with the Marketplaces internal order number.
     *
     * @param string $marketplacesInternalOrderNumber
     * @return WC_Order|null
     */
    public static function getByMarketplacesInternalOrderNumber(string $marketplacesInternalOrderNumber)
    {
        $results = get_posts([
            'post_type'   => 'shop_order',
            'fields'      => 'ids',
            'post_status' => 'any',
            'meta_key'    => OrderAdapter::MARKETPLACES_INTERNAL_ORDER_NUMBER_META_KEY,
            'meta_value'  => $marketplacesInternalOrderNumber,
        ]);

        if (! empty($results) && is_int($results[0])) {
            return CommonOrdersRepository::get($results[0]);
        }

        return null;
    }

    /**
     * Returns the orders pickup locations.
     *
     * @param Order $order
     *
     * @return array<int, string>
     * @throws Exception
     */
    public static function getPickupLocations(Order $order) : array
    {
        $locationIds = [];

        foreach ($order->getShippingItems() as $shippingItem) {
            if ($locationId = TypeHelper::string(wc_get_order_item_meta($shippingItem->getId(), 'godaddy_mwc_commerce_location_id'), '')) {
                $locationIds[$shippingItem->getId()] = $locationId;
            }
        }

        return $locationIds;
    }
}

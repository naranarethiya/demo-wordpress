<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Data store helper for orders in Marketplaces.
 */
class OrderDataStore
{
    use CanGetNewInstanceTrait;

    /**
     * Retrieves a map of order item references to order item IDs for the given order.
     *
     * @param Order $order
     * @return array<string,int>
     */
    public function getOrderItemReferenceMapping(Order $order) : array
    {
        if (empty($order->getId())) {
            return [];
        }

        $table = sprintf('%s%s', DatabaseRepository::getTablePrefix(), 'woocommerce_order_itemmeta');
        $orderItemsTable = sprintf('%s%s', DatabaseRepository::getTablePrefix(), 'woocommerce_order_items');
        $wpdb = DatabaseRepository::instance();

        $query = $wpdb->prepare("
            SELECT
                meta.order_item_id,
                meta.meta_value
            FROM
                {$table} meta
            INNER JOIN {$orderItemsTable} items USING(order_item_id)
            WHERE
                items.order_id = %d AND
                meta.meta_key = %s
        ", $order->getId(), OrderAdapter::MARKETPLACES_INTERNAL_ORDER_ITEM_ID_META_KEY);

        $results = DatabaseRepository::getResults($query); /* @phpstan-ignore-line */

        $orderItemIdMap = [];

        foreach ($results as $result) {
            $orderItemReference = (string) ArrayHelper::get($result, 'meta_value', '');
            $orderItemId = (int) ArrayHelper::get($result, 'order_item_id', 0);

            $orderItemIdMap[$orderItemReference] = $orderItemId;
        }

        return $orderItemIdMap;
    }
}

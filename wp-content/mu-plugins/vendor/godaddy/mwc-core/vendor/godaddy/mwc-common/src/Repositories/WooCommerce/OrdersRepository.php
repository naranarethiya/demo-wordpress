<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Automattic\WooCommerce\Utilities\OrderUtil;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\Traits\HasWooCommerceDataAccessorsTrait;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Order;
use WC_Order_Item;

/**
 * Repository for handling WooCommerce orders.
 *
 * @property WC_Order $object
 */
class OrdersRepository
{
    use HasWooCommerceDataAccessorsTrait;

    /**
     * Initializes a new WooCommerce order to be built.
     *
     * @param WC_Order $object
     */
    public function __construct(WC_Order $object)
    {
        $this->object = $object;
    }

    /**
     * Gets a WooCommerce order object.
     *
     * @param int order ID
     * @return WC_Order|null
     */
    public static function get(int $id)
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return null;
        }

        return wc_get_order($id) ?: null;
    }

    /**
     * Gets an array of WooCommerce order objects.
     *
     * @link https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query for accepted args and extended usage
     *
     * @param array $args
     * @return WC_Order[]
     */
    public static function query(array $args = []) : array
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return [];
        }

        /*
         * By default, wc_get_orders() returns an array of WC_Order and OrderRefund objects.
         * Given that the expected return type is `WC_Order[]`, set the `type` arg is set to `shop_order`
         * to exclude refunds from the results.
         */
        $args = array_merge($args, [
            'type' => 'shop_order',
        ]);

        return ArrayHelper::wrap(wc_get_orders($args));
    }

    /**
     * Gets a list of WooCommerce order statuses which are considered "paid".
     *
     * @return string[] array of status slugs
     */
    public static function getPaidStatuses() : array
    {
        return ArrayHelper::wrap(wc_get_is_paid_statuses());
    }

    /**
     * Starts a new instance seeding a new WooCommerce order object.
     *
     * @param array $properties optional properties to set on the order
     * @param array $metadata optional metadata to set on the order
     * @return OrdersRepository
     */
    public static function seed(array $properties = [], array $metadata = []) : OrdersRepository
    {
        return static::for(new WC_Order())->setData($properties, $metadata);
    }

    /**
     * Adds an order item to the order.
     *
     * @param WC_Order_Item $item
     * @return OrdersRepository
     */
    public function addItem(WC_Order_Item $item) : OrdersRepository
    {
        $this->object->add_item($item);

        return $this;
    }

    /**
     * Gets order type for the given WooCommerce order ID.
     *
     * @param int $orderId
     * @return string
     */
    public static function getOrderType(int $orderId) : string
    {
        $orderType = WooCommerceRepository::isCustomOrdersTableUsageEnabled() ?
            OrderUtil::get_order_type($orderId) :
            get_post_type($orderId);

        return TypeHelper::string($orderType, '');
    }

    /**
     * Checks whether the given ID belongs to a regular order (shop_order).
     */
    public static function isRegularOrder(int $orderId) : bool
    {
        return static::getOrderType($orderId) === 'shop_order';
    }
}

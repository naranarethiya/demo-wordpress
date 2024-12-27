<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Repositories\Traits\HasWooCommerceDataAccessorsTrait;
use WC_Order_Item;
use WC_Order_Item_Coupon;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Order_Item_Tax;

/**
 * Repository for handling WooCommerce order items.
 *
 * @property WC_Order_Item|WC_Order_Item_Coupon|WC_Order_Item_Fee|WC_Order_Item_Product|WC_Order_Item_Shipping|WC_Order_Item_Tax $object
 */
class OrderItemsRepository
{
    use HasWooCommerceDataAccessorsTrait;

    /**
     * Initializes a new WooCommerce order item to be built.
     *
     * @param WC_Order_Item|WC_Order_Item_Coupon|WC_Order_Item_Fee|WC_Order_Item_Product|WC_Order_Item_Shipping|WC_Order_Item_Tax $object
     */
    public function __construct(WC_Order_Item $object)
    {
        $this->object = $object;
    }

    /**
     * Starts a new instance seeding a new WooCommerce coupon order item object.
     *
     * @param array $properties optional properties to set on the order item
     * @param array $metadata optional metadata to set on the order item
     */
    public static function seedCouponOrderItem(array $properties = [], array $metadata = []) : OrderItemsRepository
    {
        return static::for(new WC_Order_Item_Coupon())->setData($properties, $metadata);
    }

    /**
     * Starts a new instance seeding a new WooCommerce fee order item object.
     *
     * @param array $properties optional properties to set on the order item
     * @param array $metadata optional metadata to set on the order item
     * @return OrderItemsRepository
     */
    public static function seedFeeOrderItem(array $properties = [], array $metadata = []) : OrderItemsRepository
    {
        return static::for(new WC_Order_Item_Fee())->setData($properties, $metadata);
    }

    /**
     * Starts a new instance seeding a new WooCommerce product order item object.
     *
     * @param array $properties optional properties to set on the order item
     * @param array $metadata optional metadata to set on the order item
     * @return OrderItemsRepository
     */
    public static function seedProductOrderItem(array $properties = [], array $metadata = []) : OrderItemsRepository
    {
        return static::for(new WC_Order_Item_Product())->setData($properties, $metadata);
    }

    /**
     * Starts a new instance seeding a new WooCommerce shipping order item object.
     *
     * @param array $properties optional properties to set on the order item
     * @param array $metadata optional metadata to set on the order item
     * @return OrderItemsRepository
     */
    public static function seedShippingOrderItem(array $properties = [], array $metadata = []) : OrderItemsRepository
    {
        return static::for(new WC_Order_Item_Shipping())->setData($properties, $metadata);
    }

    /**
     * Starts a new instance seeding a new WooCommerce tax order item object.
     *
     * @param array $properties optional properties to set on the order item
     * @param array $metadata optional metadata to set on the order item
     * @return OrderItemsRepository
     */
    public static function seedTaxOrderItem(array $properties = [], array $metadata = []) : OrderItemsRepository
    {
        return static::for(new WC_Order_Item_Tax())->setData($properties, $metadata);
    }
}

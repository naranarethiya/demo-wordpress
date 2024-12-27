<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository as CommonOrdersRepository;
use WC_Order;

/**
 * Orders repository to handle recoverable orders.
 */
class OrdersRepository extends CommonOrdersRepository
{
    /** @var string meta key to hold the recovery status of an order */
    public const META_KEY_RECOVERY_STATUS = '_mwc_order_recovery_status';

    /** @var string meta key to hold the checkout ID related to an order in recovery */
    public const META_KEY_RECOVERY_CHECKOUT_ID = '_mwc_order_recoverable_checkout_id';

    /** @var string recovery status of an order that is about to be recovered (pending paid order status) */
    public const ORDER_STATUS_PENDING_RECOVERY = 'pending_recovery';

    /** @var string recovery status of an order that has been fully recovered */
    public const ORDER_STATUS_RECOVERED = 'recovered';

    /**
     * Gets the recovery status of an order.
     *
     * @param int $orderId
     * @return string|null
     */
    public static function getOrderRecoveryStatus(int $orderId) : ?string
    {
        $status = static::getOrderMeta($orderId, self::META_KEY_RECOVERY_STATUS);

        /* @TODO confirm if an order that was not placed as the result of a cart recovery campaign should have a different status than `null` {unfulvio 2022-03-23} */
        return ArrayHelper::contains([static::ORDER_STATUS_PENDING_RECOVERY, static::ORDER_STATUS_RECOVERED], $status)
            ? $status
            : null;
    }

    /**
     * Gets the value of the specified metadata key for an order identified with the given ID.
     *
     * @param int $orderId
     * @param string $key
     * @return mixed
     */
    protected static function getOrderMeta(int $orderId, string $key)
    {
        if (! $wooOrder = static::get($orderId)) {
            return null;
        }

        return $wooOrder->get_meta($key);
    }

    /**
     * Flags an order as pending recovery.
     *
     * @param WC_Order $order
     * @return WC_Order
     */
    public static function flagOrderAsPendingRecovery(WC_Order $order) : WC_Order
    {
        $order->update_meta_data(static::META_KEY_RECOVERY_STATUS, static::ORDER_STATUS_PENDING_RECOVERY);

        return $order;
    }

    /**
     * Determines if an order is pending recovery.
     *
     * @param int $orderId
     * @return bool
     */
    public static function isOrderPendingRecovery(int $orderId) : bool
    {
        return static::ORDER_STATUS_PENDING_RECOVERY === static::getOrderRecoveryStatus($orderId);
    }

    /**
     * Flags an order as recovered.
     *
     * @param WC_Order $order
     * @return WC_Order
     */
    public static function flagOrderAsRecovered(WC_Order $order) : WC_Order
    {
        $order->update_meta_data(static::META_KEY_RECOVERY_STATUS, static::ORDER_STATUS_RECOVERED);

        return $order;
    }

    /**
     * Determines if an order is recovered.
     *
     * @param int $orderId
     * @return bool
     */
    public static function isOrderRecovered(int $orderId) : bool
    {
        return static::ORDER_STATUS_RECOVERED === static::getOrderRecoveryStatus($orderId);
    }

    /**
     * Gets the recoverable checkout ID associated to an order.
     *
     * We may not be able to return a whole checkout once it's been cascade deleted following an order submission.
     * But we may have been able to store the related checkout ID in the order meta.
     *
     * @param int $orderId
     * @return int|null
     */
    public static function getOrderRecoverableCheckoutId(int $orderId) : ?int
    {
        $checkoutId = static::getOrderMeta($orderId, self::META_KEY_RECOVERY_CHECKOUT_ID);

        return is_numeric($checkoutId) ? (int) $checkoutId : null;
    }

    /**
     * Associates a recoverable checkout to an order.
     *
     * @param WC_Order $order
     * @param int $checkoutId
     * @return WC_Order
     */
    public static function setOrderRecoverableCheckoutId(WC_Order $order, int $checkoutId) : WC_Order
    {
        $order->update_meta_data(self::META_KEY_RECOVERY_CHECKOUT_ID, $checkoutId);

        return $order;
    }
}

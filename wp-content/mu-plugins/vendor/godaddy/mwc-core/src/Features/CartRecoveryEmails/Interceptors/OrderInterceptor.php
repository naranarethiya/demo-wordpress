<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\CheckoutRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\OrdersRepository;
use WC_Checkout;
use WC_Order;

/**
 * Intercepts WooCommerce orders that may be recovered via the cart recovery emails feature.
 */
class OrderInterceptor extends AbstractInterceptor
{
    /**
     * Hooks into WooCommerce orders.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('woocommerce_checkout_order_processed')
            ->setHandler([$this, 'onWooCommerceCheckoutOrderProcessed'])
            ->setArgumentsCount(3)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_order_status_changed')
            ->setHandler([$this, 'onWooCommerceOrderStatusChanged'])
            ->setArgumentsCount(4)
            ->execute();
    }

    /**
     * Maybe flags an order as pending recovery.
     *
     * If the customer followed a cart recovery link, the order is pending recovery.
     *
     * @see WC_Checkout::process_checkout()
     *
     * @internal
     *
     * @param int|mixed $orderId the order ID
     * @param array|mixed $postedData order submission posted data
     * @param WC_Order|mixed $order the order object
     * @throws Exception
     */
    public function onWooCommerceCheckoutOrderProcessed($orderId, $postedData, $order)
    {
        $pendingRecovery = Checkout::STATUS_PENDING_RECOVERY === SessionRepository::get(Checkout::STATUS_SESSION_KEY);
        $checkoutId = SessionRepository::get(CheckoutRepository::SESSION_KEY_CHECKOUT_ID);

        if ($pendingRecovery && $checkoutId && $order instanceof WC_Order) {
            $order = OrdersRepository::setOrderRecoverableCheckoutId($order, (int) $checkoutId);
            $order = OrdersRepository::flagOrderAsPendingRecovery($order);
            $order->save_meta_data();
            $order->save();
        }
    }

    /**
     * Maybe marks an order as recovered.
     *
     * If an order pending recovery reaches a processing status, the order is considered recovered.
     *
     * @see WC_Order::status_transition()
     *
     * @internal
     *
     * @param int|mixed $orderId the order ID
     * @param string|mixed $fromStatus order status transitioning from
     * @param string|mixed $toStatus order status transitioning to
     * @param WC_Order|mixed $order the order object
     */
    public function onWooCommerceOrderStatusChanged($orderId, $fromStatus, $toStatus, $order)
    {
        $recoverable = OrdersRepository::isOrderPendingRecovery((int) $orderId)
            && ArrayHelper::contains(wc_get_is_paid_statuses(), $toStatus);

        if ($recoverable && $order instanceof WC_Order) {
            $order = OrdersRepository::flagOrderAsRecovered($order);
            $order->save_meta_data();
            $order->save();
        }
    }
}

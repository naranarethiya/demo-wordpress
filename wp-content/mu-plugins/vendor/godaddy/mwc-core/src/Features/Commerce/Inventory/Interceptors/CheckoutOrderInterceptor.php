<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\OrderReservationsService;
use WC_Order;

/**
 * Interceptor to handle the checkout order.
 */
class CheckoutOrderInterceptor extends AbstractInterceptor
{
    protected OrderReservationsService $orderReservationsService;

    /**
     * @param OrderReservationsService $orderReservationsService
     */
    public function __construct(OrderReservationsService $orderReservationsService)
    {
        $this->orderReservationsService = $orderReservationsService;
    }

    /**
     * Adds the hook to register.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('woocommerce_checkout_order_created')
            ->setHandler([$this, 'onCheckoutOrderCreated'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_hold_stock_for_checkout')
            ->setHandler('__return_false')
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_payment_complete_reduce_order_stock')
            ->setHandler('__return_false')
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_prevent_adjust_line_item_product_stock')
            ->setHandler('__return_true')
            ->execute();
    }

    /**
     * Attempt creating reservations for the given order's line items.
     *
     * This is fired when the WooCommerce creates the initial Pending order.
     *
     * @param mixed $order
     *
     * @throws Exception
     */
    public function onCheckoutOrderCreated($order) : void
    {
        if (! $order instanceof WC_Order) {
            return;
        }

        if ($this->orderReservationsService->orderHasFailedReservations($order)) {
            $this->bailOrderCreationProcess($order);
        }
    }

    /**
     * Bails the order creation process.
     *
     * This is called during the woocommerce_checkout_order_created hook in the case of inventory API errors, and is
     * used to prevent WooCommerce from proceeding with order payment.
     *
     * @param WC_Order $order
     *
     * @throws Exception
     */
    protected function bailOrderCreationProcess(WC_Order $order) : void
    {
        $redirectUrl = $order->get_checkout_order_received_url();

        // if this is AJAX, send a success response
        if (WordPressRepository::isAjax()) {
            (new Response)
                ->setBody([
                    'result'   => 'success',
                    'redirect' => $redirectUrl,
                ])
                ->send();
        }

        // otherwise, redirect
        Redirect::to($redirectUrl)
            ->setSafe(true)
            ->execute();
    }
}

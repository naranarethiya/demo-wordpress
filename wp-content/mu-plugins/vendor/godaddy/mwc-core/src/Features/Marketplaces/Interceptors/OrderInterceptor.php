<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Marketplaces;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use WC_Order;

/**
 * Handles orders that may be tied to a Marketplaces order.
 */
class OrderInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_admin_order_should_render_refunds')
            ->setHandler([$this, 'removeRefundActionsForMarketplacesOrders'])
            ->setArgumentsCount(3)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_order_item_add_action_buttons')
            ->setHandler([$this, 'removeRefundButtonForMarketplacesOrders'])
            ->setCondition(static function () {
                $wcVersion = WooCommerceRepository::getWooCommerceVersion();

                return $wcVersion && ! WordPressRepository::isCliMode() && version_compare($wcVersion, '6.4', '<=');
            })
            ->execute();

        Register::action()
            ->setGroup('update_postmeta')
            ->setHandler([$this, 'maybeAddWalmartOrderStatusNote'])
            ->setArgumentsCount(4)
            ->execute();
    }

    /**
     * Removes refund button for Marketplaces orders.
     *
     * @TODO Remove this callback when WooCommerce 6.4 is the minimum supported version {unfulvio 2022-05-11}
     * @see OrderInterceptor::removeRefundActionsForMarketplacesOrders()
     * @internal
     *
     * @param WC_Order|mixed $order
     * @return void
     */
    public function removeRefundButtonForMarketplacesOrders($order) : void
    {
        try {
            if (! $order instanceof WC_Order || ! OrderAdapter::getNewInstance($order)->convertFromSource()->hasMarketplacesChannel()) {
                return;
            }
        } catch (Exception $exception) {
            // since we are in a callback context, we should catch any exceptions and just report them to Sentry
            new SentryException($exception->getMessage(), $exception);

            return;
        }

        wc_enqueue_js("jQuery('button.button.refund-items').hide();");
    }

    /**
     * Removes refund actions for Marketplaces orders.
     *
     * @internal
     *
     * @param bool|mixed $shouldRenderRefunds
     * @param int|mixed $orderId
     * @param WC_Order|mixed $order
     * @return bool|mixed
     */
    public function removeRefundActionsForMarketplacesOrders($shouldRenderRefunds, $orderId, $order)
    {
        if (! $shouldRenderRefunds || ! $orderId || ! $order instanceof WC_Order) {
            return $shouldRenderRefunds;
        }

        try {
            $order = OrderAdapter::getNewInstance($order)->convertFromSource();
            $shouldRenderRefunds = ! $order->hasMarketplacesChannel();
        } catch (Exception $exception) {
            // since we are in a callback context, we should catch any exceptions and just report them to Sentry
            new SentryException($exception->getMessage(), $exception);
        }

        return $shouldRenderRefunds;
    }

    /**
     * May add an order note for Walmart orders that are pending return or returned.
     *
     * @internal
     *
     * @param int|mixed $metaId
     * @param int|mixed $objectId
     * @param string|mixed $metaKey
     * @param mixed $metaValue
     * @return void
     */
    public function maybeAddWalmartOrderStatusNote($metaId, $objectId, $metaKey, $metaValue) : void
    {
        // bail if not the marketplaces status meta or if it's not one of the relevant statuses
        if (OrderAdapter::MARKETPLACES_ORDER_STATUS !== $metaKey || ! is_string($metaValue) || ! StringHelper::contains($metaValue, ['return_pending', 'returned'])) {
            return;
        }

        $sourceOrder = OrdersRepository::get($objectId);

        // bail if the metadata is not for an order object
        if (! $sourceOrder instanceof WC_Order) {
            return;
        }

        try {
            $nativeOrder = OrderAdapter::getNewInstance($sourceOrder)->convertFromSource();
        } catch (Exception $exception) {
            // since we are in a hook callback context, we bail out when an exception is thrown
            return;
        }

        // bail if not a Walmart order
        if (Channel::TYPE_WALMART !== $nativeOrder->getMarketplacesChannelType()) {
            return;
        }

        switch ($metaValue) {
            case 'return_pending':
                if ($orderReference = $nativeOrder->getMarketplacesChannelOrderReference()) {
                    $this->addWalmartPendingReturnOrderNote($sourceOrder, $orderReference);
                }
                break;
            case 'returned':
                $this->addWalmartReturnedOrderNote($sourceOrder);
                break;
        }
    }

    /**
     * Adds a WooCommerce order note for Walmart orders that have been returned.
     *
     * @see OrderInterceptor::maybeAddWalmartOrderStatusNote()
     *
     * @param WC_Order $sourceOrder
     * @return void
     */
    protected function addWalmartReturnedOrderNote(WC_Order $sourceOrder) : void
    {
        $sourceOrder->add_order_note(__('Return accepted via Walmart sales channel.', 'mwc-core'));
    }

    /**
     * Adds a WooCommerce order note for Walmart orders that are pending return.
     *
     * @see OrderInterceptor::maybeAddWalmartOrderStatusNote()
     *
     * @param WC_Order $sourceOrder
     * @param string $orderReference
     * @return void
     */
    protected function addWalmartPendingReturnOrderNote(WC_Order $sourceOrder, string $orderReference) : void
    {
        try {
            $orderDetailsUrl = Marketplaces::getMarketplacesUrl('/orders/'.$orderReference);
        } catch (Exception $exception) {
            // since we are in a hook callback context, we bail out when an exception is thrown
            return;
        }

        $sourceOrder->add_order_note(sprintf(
            /* translators: Placeholders: %1$s - Opening HTML <a> link tag, %2$s - Closing HTML </a> link tag */
            __('Return requested via Walmart sales channel. %1$sView request%2$s.', 'mwc-core'),
            '<a href="'.esc_url($orderDetailsUrl).'" target="_blank">',
            '</a>'
        ));
    }
}

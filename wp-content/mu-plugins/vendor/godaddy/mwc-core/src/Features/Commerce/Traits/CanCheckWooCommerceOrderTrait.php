<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CheckoutDraftOrderStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\OrdersIntegration;
use WC_Order;

trait CanCheckWooCommerceOrderTrait
{
    /**
     * Check if the given WooCommerce order is incomplete.
     *
     * An order is considered incomplete if it is in the "checkout-draft" status.
     *
     * @param WC_Order $wooOrder
     * @return bool
     */
    protected function isWooCommerceOrderIncomplete(WC_Order $wooOrder) : bool
    {
        return $wooOrder->get_status() === (new CheckoutDraftOrderStatus)->getName();
    }

    /**
     * Determines whether we can use the given input to create a WooCommerce order in the Commerce platform.
     *
     * @param mixed $wooOrder
     * @phpstan-assert-if-true WC_Order $wooOrder
     */
    protected function canWriteWooCommerceOrderInPlatform($wooOrder) : bool
    {
        return $this->isWooCommerceOrder($wooOrder) &&
            OrdersIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE);
    }

    /**
     * Determines whether we can use the given input to create a WooCommerce order in the Commerce platform.
     *
     * @param mixed $wooOrder
     * @phpstan-assert-if-true WC_Order $wooOrder
     */
    protected function canReadWooCommerceOrderFromPlatform($wooOrder) : bool
    {
        return $this->isWooCommerceOrder($wooOrder) &&
            OrdersIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Determines whether the given input is a WooCommerce order.
     *
     * @param mixed $wooOrder
     * @phpstan-assert-if-true WC_Order $wooOrder
     */
    protected function isWooCommerceOrder($wooOrder) : bool
    {
        return $wooOrder instanceof WC_Order;
    }
}

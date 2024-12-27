<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\LineItemReservedEvent;
use WC_Product;

class LineItemReservedNotificationsSubscriber implements SubscriberContract
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if (! $event instanceof LineItemReservedEvent || ! $product = $event->lineItem->getProduct()) {
            return;
        }

        $currentStock = $product->get_stock_quantity();

        if ($currentStock <= $this->getOutOfStockNotificationThreshold()) {
            do_action('woocommerce_no_stock', $product);
        } elseif ($currentStock <= $this->getLowStockNotificationThreshold($product)) {
            do_action('woocommerce_low_stock', $product);
        }
    }

    /**
     * Gets the site's "out-of-stock" notification threshold.
     *
     * @return int
     */
    protected function getOutOfStockNotificationThreshold() : int
    {
        return TypeHelper::int(get_option('woocommerce_notify_no_stock_amount', 0), 0);
    }

    /**
     * Gets the site's "low stock" notification threshold.
     *
     * @param WC_Product $product
     *
     * @return int
     */
    protected function getLowStockNotificationThreshold(WC_Product $product) : int
    {
        return TypeHelper::int(wc_get_low_stock_amount($product), 0);
    }
}

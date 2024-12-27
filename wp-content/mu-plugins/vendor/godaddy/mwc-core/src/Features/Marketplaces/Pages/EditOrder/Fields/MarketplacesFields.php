<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\EditOrder\Fields;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

/**
 * This class is responsible for outputting and handling Marketplaces fields displayed in the Edit Order page.
 */
class MarketplacesFields implements ComponentContract
{
    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('woocommerce_admin_order_data_after_order_details')
            ->setHandler([$this, 'renderMarketplacesFields'])
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();
    }

    /**
     * Determines if the assets should be enqueued.
     *
     * @return bool
     */
    protected function shouldEnqueueAssets() : bool
    {
        $screen = WordPressRepository::getCurrentScreen();

        return $screen && 'edit_order' === $screen->getPageId();
    }

    /**
     * Enqueues Marketplaces assets for the order edit screen page.
     *
     * @internal
     *
     * @return void
     * @throws Exception
     */
    public function enqueueAssets() : void
    {
        if (! $this->shouldEnqueueAssets()) {
            return;
        }

        Enqueue::style()
            ->setHandle('gd-marketplaces-order-edit-page')
            ->setSource(WordPressRepository::getAssetsUrl('css/features/marketplaces/admin/order-edit-page.css'))
            ->execute();

        Enqueue::script()
            ->setHandle('gd-marketplaces-order-edit-page')
            ->setSource(WordPressRepository::getAssetsUrl('js/features/marketplaces/admin/order-edit-page.js'))
            ->setDependencies(['jquery'])
            ->setDeferred(true)
            ->execute();
    }

    /**
     * Outputs the Marketplaces fields for the current order.
     *
     * @param WC_Order|mixed $order
     * @return void
     */
    public function renderMarketplacesFields($order) : void
    {
        if (! $order instanceof WC_Order) {
            return;
        }

        try {
            /** @var Order $order */
            $order = OrderAdapter::getNewInstance($order)->convertFromSource();

            if (! $order->hasMarketplacesChannel()) {
                return;
            }
        } catch (Exception $exception) {
            return;
        } ?>
        <div id="gd-marketplaces-order-fields" style="display: none;">
            <h3><?php esc_html_e('Marketplaces', 'mwc-core'); ?></h3>
            <div class="gd-marketplaces-sales-channel">
                <h4><?php esc_html_e('Sales Channel', 'mwc-core'); ?></h4>
                <p><?php echo ChannelRepository::getLabel($order->getMarketplacesChannelType() ?: '', true); ?></p>
            </div>
            <div class="gd-marketplaces-order-number">
                <h4><?php esc_html_e('Sales Channel Order Number', 'mwc-core'); ?></h4>
                <p><?php echo esc_html($order->getMarketplacesDisplayOrderNumber() ?: ''); ?></p>
            </div>
        </div>
        <?php
    }
}

<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\CanHaveWooCommerceOrderContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Traits\CanHaveWooCommerceOrderTrait;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WC_Order;
use WC_Order_Item;
use WC_Order_Refund;

/**
 * Order item adapter abstract.
 */
abstract class AbstractOrderItemAdapter implements CanHaveWooCommerceOrderContract
{
    use CanGetNewInstanceTrait;
    use CanHaveWooCommerceOrderTrait;

    /** @var WC_Order_Item */
    protected $source;

    /**
     * Gets the currency associated with the item.
     *
     * @since 3.4.1
     *
     * @return string
     */
    protected function getCurrency() : string
    {
        $wooOrder = $this->getWooCommerceOrder();

        if ($wooOrder && $orderCurrency = $wooOrder->get_currency()) {
            return $orderCurrency;
        }

        return WooCommerceRepository::getCurrency();
    }

    /**
     * Converts an order item amount from source.
     *
     * @since 3.4.1
     *
     * @param float $amount
     * @return CurrencyAmount
     */
    protected function convertCurrencyAmountFromSource(float $amount) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter($amount, $this->getCurrency()))->convertFromSource();
    }

    /**
     * Converts a currency amount to float for the order item.
     *
     * @since 3.4.1
     *
     * @param CurrencyAmount $amount
     * @return float
     */
    protected function convertCurrencyAmountToSource(CurrencyAmount $amount) : float
    {
        return (float) (new CurrencyAmountAdapter(0.0, $this->getCurrency()))->convertToSource($amount);
    }

    /**
     * Gets source order instance from source line item.
     *
     * @return WC_Order|null
     */
    protected function getOrderFromSource() : ?WC_Order
    {
        /**
         * {@see WC_Order_Item::get_order()} calls {@see wc_get_order()} which can return false and {@see WC_Order_Refund} too.
         *
         * @var WC_Order|WC_Order_Refund|false $wooOrder
         */
        $wooOrder = $this->source->get_order();

        return $wooOrder instanceof WC_Order ? $wooOrder : null;
    }

    /**
     * Gets memoized WooCommerce order.
     *
     * @return WC_Order|null
     */
    public function getWooCommerceOrder() : ?WC_Order
    {
        return $this->wooCommerceOrder ??= $this->getOrderFromSource();
    }

    /**
     * Gets a new instance of the adapter with the given item and order instances.
     *
     * @param WC_Order_Item $source
     * @param WC_Order|null $sourceOrder
     * @return static
     */
    public static function for(object $source, ?WC_Order $sourceOrder = null)
    {
        return static::getNewInstance($source)->setWooCommerceOrder($sourceOrder);
    }
}

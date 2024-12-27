<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Models\Orders\ShippingItem;
use WC_Order;
use WC_Order_Item_Shipping;

/**
 * Order shipping item adapter.
 *
 * Converts between a native order shipping item object and a WooCommerce order shipping item object.
 *
 * @property WC_Order_Item_Shipping $source
 * @method static static getNewInstance(WC_Order_Item_Shipping $source)
 * @method static static for(WC_Order_Item_Shipping $source, ?WC_Order $sourceOrder = null)
 */
class ShippingItemAdapter extends AbstractOrderItemAdapter implements DataSourceAdapterContract
{
    /**
     * Order shipping item adapter constructor.
     *
     * @param WC_Order_Item_Shipping $source
     */
    public function __construct(WC_Order_Item_Shipping $source)
    {
        $this->source = $source;
    }

    /**
     * Converts a WooCommerce order shipping item to a native order shipping item.
     *
     * @return ShippingItem
     */
    public function convertFromSource() : ShippingItem
    {
        return (new ShippingItem())
            ->setId($this->source->get_id())
            ->setLabel($this->source->get_name())
            ->setName($this->source->get_method_id())
            ->setTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_tax()))
            ->setTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total()));
    }

    /**
     * Converts a native order line item into a WooCommerce order shipping item.
     *
     * @param ShippingItem|null $shippingItem
     * @return WC_Order_Item_Shipping
     * @throws AdapterException
     */
    public function convertToSource($shippingItem = null) : WC_Order_Item_Shipping
    {
        if (! $shippingItem instanceof ShippingItem) {
            return $this->source;
        }

        try {
            $this->source->set_id($shippingItem->getId());
            $this->source->set_name($shippingItem->getLabel());
            $this->source->set_method_id($shippingItem->getName());
            $this->source->set_total($this->convertCurrencyAmountToSource($shippingItem->getTotalAmount()));
        } catch (Exception $exception) {
            throw new AdapterException($exception->getMessage(), $exception);
        }

        return $this->source;
    }
}

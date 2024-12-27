<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\LineItemAdapter as CommonLineItemAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\LineItem as MarketplacesLineItem;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters\LineItemFulfillmentStatusAdapter;
use WC_Order_Item_Product;

class LineItemAdapter extends CommonLineItemAdapter
{
    /** @var string an option containing a list of values */
    const FULFILLMENT_CHANNEL_ID_META_KEY = '_mwc_fulfillment_channel_id';

    /** @var class-string<LineItemFulfillmentStatusAdapter> the line item fulfillment status adapter */
    protected string $lineItemFulfillmentStatusAdapter = LineItemFulfillmentStatusAdapter::class;

    /**
     * @return LineItem
     */
    public function convertFromSource() : LineItem
    {
        $lineItem = $this->parentConvertFromSource();

        if ($fulfillmentChannelId = TypeHelper::string($this->source->get_meta(static::FULFILLMENT_CHANNEL_ID_META_KEY), '')) {
            $lineItem->setFulfillmentChannelId($fulfillmentChannelId);
        }

        return $lineItem;
    }

    /**
     * @param LineItem|null $lineItem
     * @return WC_Order_Item_Product
     */
    public function convertToSource(LineItem $lineItem = null) : WC_Order_Item_Product
    {
        $wcOrderItem = $this->parentConvertToSource($lineItem);

        $this->convertFulfillmentStatusToSource($wcOrderItem, $lineItem);

        if ($lineItem instanceof MarketplacesLineItem && $lineItem->getOrderItemReference()) {
            $wcOrderItem->update_meta_data(
                OrderAdapter::MARKETPLACES_INTERNAL_ORDER_ITEM_ID_META_KEY,
                $lineItem->getOrderItemReference()
            );
        }

        return $wcOrderItem;
    }

    /**
     * @codeCoverageIgnore Isolated to its own test to make parent method mockable.
     * @return LineItem
     */
    protected function parentConvertFromSource() : LineItem
    {
        return parent::convertFromSource();
    }

    /**
     * @codeCoverageIgnore Isolated to its own test to make parent method mockable.
     * @param LineItem|null $lineItem
     * @return WC_Order_Item_Product
     */
    protected function parentConvertToSource(?LineItem $lineItem) : WC_Order_Item_Product
    {
        return parent::convertToSource($lineItem);
    }

    /**
     * Sets the fulfillment status of the given line item model using the information in the {@see WC_Order_Item_Product} line item instance.
     */
    protected function convertFulfillmentStatusToSource(WC_Order_Item_Product $source, ?LineItem $lineItem) : void
    {
        $this->lineItemFulfillmentStatusAdapter::getNewInstance($source)->convertToSource($lineItem);
    }
}

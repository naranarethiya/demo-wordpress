<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\LineItemAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use WC_Order_Item_Product;

class LineItemFulfillmentStatusAdapter extends FulfillmentStatusAdapter
{
    /** @var WC_Order_Item_Product the source order item */
    protected $source;

    public function __construct(WC_Order_Item_Product $orderItem)
    {
        $this->source = $orderItem;
    }

    /**
     * @param LineItem|null $lineItem
     * @return LineItem
     */
    public function convertFromSource(LineItem $lineItem = null) : LineItem
    {
        if (is_null($lineItem)) {
            $lineItem = LineItemAdapter::getNewInstance($this->source)->convertFromSource();
        }

        $statusMeta = TypeHelper::string($this->source->get_meta(FulfillmentStatusAdapter::META_KEY), '');
        $lineItem->setFulfillmentStatus($this->getFulfillmentStatusByName($statusMeta));

        return $lineItem;
    }

    /**
     * @param LineItem|null $lineItem
     * @return WC_Order_Item_Product
     */
    public function convertToSource(LineItem $lineItem = null) : WC_Order_Item_Product
    {
        if (is_null($lineItem)) {
            return $this->source;
        }

        $fulfillmentStatus = $lineItem->getFulfillmentStatus();

        $this->source->update_meta_data(
            FulfillmentStatusAdapter::META_KEY,
            $fulfillmentStatus ? $fulfillmentStatus->getName() : ''
        );

        return $this->source;
    }
}
